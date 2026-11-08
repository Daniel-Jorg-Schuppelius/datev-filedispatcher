#!/bin/bash
#
# Überwacht das Eingangsverzeichnis und übergibt neue Dateien an den DATEV File Dispatcher.
# Liegengebliebene Dateien (Dienststart, DATEV-Server in der Sicherung, ...) werden beim
# Start und danach regelmäßig erneut versucht.
#
# Einstellungen per Umgebungsvariable (systemd: EnvironmentFile=/etc/default/filedispatcher):
#   FILEDISPATCHER_WORKDIR          überwachtes Eingangsverzeichnis               (/opt/internal_dispatcher/)
#   FILEDISPATCHER_LOCKDIR          Lock-Verzeichnis, nur für den Dienstbenutzer  ($RUNTIME_DIRECTORY bzw. /run/filedispatcher)
#   FILEDISPATCHER_MAX_JOBS         parallele Verarbeitungen je Quelle            (4)
#   FILEDISPATCHER_RESCAN_INTERVAL  Sekunden zwischen zwei Rescans, 0 = aus       (1800)
#   FILEDISPATCHER_UMASK            umask für neu erzeugte Dateien                (0027)
#   FILEDISPATCHER_CONFIG           Pfad zur config.json                          (<projekt>/config/config.json)
#   DATEV_API_USER, DATEV_API_PASSWORD, DATEV_API_URL
#                                   Zugangsdaten der DATEV-API, haben Vorrang vor der config.json

set -u

# Eigene Prozessgruppe, damit beim Beenden alle Kindprozesse mitbeendet werden, aber nur diese.
# Unter systemd und start-stop-daemon --background ist das bereits der Fall.
if [ -z "${FILEDISPATCHER_SETSID:-}" ] && command -v setsid > /dev/null 2>&1 && command -v ps > /dev/null 2>&1 \
    && [ "$(ps -o pgid= -p $$ | tr -d ' ')" != "$$" ]; then
    export FILEDISPATCHER_SETSID=1
    exec setsid "$BASH" "$0" "$@"
fi

CODEDIR="$(cd "$(dirname "$0")" && pwd)"
DISPATCHER="$CODEDIR/../src/DatevFileDispatcher.php"
WORKDIR="${FILEDISPATCHER_WORKDIR:-/opt/internal_dispatcher/}"
LOCKDIR="${FILEDISPATCHER_LOCKDIR:-${RUNTIME_DIRECTORY:-/run/filedispatcher}}"
MAX_JOBS="${FILEDISPATCHER_MAX_JOBS:-4}"
RESCAN_INTERVAL="${FILEDISPATCHER_RESCAN_INTERVAL:-1800}"
CONFIG_FILE="${FILEDISPATCHER_CONFIG:-$CODEDIR/../config/config.json}"
TAG="$(basename "$0")"

# Logdateien (auch rotierte) und Lockfiles nicht verarbeiten. inotifywait nutzt POSIX-ERE, daher [0-9] statt \d.
EXCLUDE_REGEX='(^|/)log\.txt(\.[0-9]{8}_[0-9]{6})?$|\.lock$'

# Neu erzeugte Dateien (PDF aus TIFF, ZIP-Inhalte, Logs): Gruppe darf lesen, alle anderen nichts.
umask "${FILEDISPATCHER_UMASK:-0027}"

# Unter systemd landen stdout/stderr im Journal, sonst über logger im Syslog.
if [ -z "${JOURNAL_STREAM:-}" ]; then
    if ! command -v logger > /dev/null 2>&1; then
        echo "Fehler: logger ist nicht installiert." >&2
        exit 1
    fi
    exec 1> >(logger -t "$TAG") 2> >(logger -t "$TAG" -p user.err)
fi

log() {
    echo "$*"
}

fail() {
    echo "Fehler: $*" >&2
    exit 1
}

check_command() {
    command -v "$1" > /dev/null 2>&1 || fail "$1 ist nicht installiert. Bitte installieren Sie $1, bevor Sie fortfahren."
}

check_command inotifywait
check_command php
check_command flock
check_command md5sum
check_command stat

[ -d "$WORKDIR" ] || fail "Das Arbeitsverzeichnis $WORKDIR existiert nicht."
[ -f "$DISPATCHER" ] || fail "Der Dispatcher $DISPATCHER wurde nicht gefunden."

# Lockfiles nur in einem eigenen Verzeichnis anlegen, das kein anderer Benutzer beschreiben kann:
# Die Umleitung auf das Lockfile folgt Symlinks und würde sonst beliebige Dateien überschreiben.
[ -L "$LOCKDIR" ] && fail "Das Lock-Verzeichnis $LOCKDIR ist ein symbolischer Link."
mkdir -p "$LOCKDIR" || fail "Das Lock-Verzeichnis $LOCKDIR konnte nicht angelegt werden."
[ -O "$LOCKDIR" ] || fail "Das Lock-Verzeichnis $LOCKDIR gehört nicht dem Dienstbenutzer $(id -un)."
chmod 0700 "$LOCKDIR" || fail "Die Rechte des Lock-Verzeichnisses $LOCKDIR konnten nicht gesetzt werden."

# Die Konfiguration enthält ggf. Zugangsdaten und darf nicht für alle lesbar sein.
if [ -f "$CONFIG_FILE" ]; then
    perms="$(stat -c '%a' "$CONFIG_FILE" 2> /dev/null || true)"
    if [ -n "$perms" ] && [ "${perms: -1}" != "0" ]; then
        log "Warnung: $CONFIG_FILE ist für alle Benutzer lesbar (Rechte $perms). Empfohlen: chmod 600 bzw. 640."
    fi
fi

# Verarbeitet eine Datei, sofern sie nicht bereits von einem anderen Prozess bearbeitet wird.
process_file() {
    local filepath="$1"
    local lockfile
    lockfile="$LOCKDIR/$(printf '%s' "$filepath" | md5sum | cut -d' ' -f1).lock"

    (
        if ! flock -n 9; then
            log "Datei wird bereits verarbeitet, überspringe: $filepath"
            exit 0
        fi

        # Kurze Pause, damit die Datei vollständig geschrieben ist
        sleep 1

        if [ -f "$filepath" ]; then
            log "Verarbeite: $filepath"
            php "$DISPATCHER" "$filepath"
        else
            log "Datei existiert nicht mehr: $filepath"
        fi

        rm -f "$lockfile"
    ) 9> "$lockfile"
}

# Wartet, bis weniger als MAX_JOBS Verarbeitungen dieser Shell laufen.
throttle() {
    while [ "$(jobs -rp | wc -l)" -ge "$MAX_JOBS" ]; do
        wait -n 2> /dev/null || true
    done
}

# Verarbeitet alle noch liegenden Dateien. Ignorierte Dateien und ExcludedFolders filtert der Dispatcher.
rescan() {
    local min_age="$1"

    php "$DISPATCHER" --list "$WORKDIR" "$min_age" | {
        while IFS= read -r -d '' filepath; do
            throttle
            process_file "$filepath" &
        done
        wait
    }
}

cleanup() {
    trap '' TERM INT USR1
    log "Beende filedispatcher."
    # Beendet die eigene Prozessgruppe: inotifywait, Rescans und laufende Verarbeitungen.
    kill -TERM 0 2> /dev/null
    exit 0
}

trap cleanup TERM INT USR1

log "Starte filedispatcher (Arbeitsverzeichnis: $WORKDIR, parallel: $MAX_JOBS, Rescan: ${RESCAN_INTERVAL}s)"

# Bestand beim Start abarbeiten
rescan 0 &

# Regelmäßig liegengebliebene Dateien erneut versuchen (nur Dateien, die seit einer Minute unverändert sind)
if [ "$RESCAN_INTERVAL" -gt 0 ]; then
    (
        while sleep "$RESCAN_INTERVAL"; do
            rescan 60
        done
    ) &
fi

# Die Überwachung läuft im Hintergrund, damit "wait" ein TERM sofort an cleanup weitergibt.
# Eine Pipeline im Vordergrund würde den Trap bis zu ihrem Ende aufschieben.
while true; do
    # %e enthält keine Leerzeichen, der Rest der Zeile ist der Pfad (auch mit Leerzeichen).
    inotifywait --monitor --quiet --recursive \
        --event close_write --event moved_to \
        --exclude "$EXCLUDE_REGEX" \
        --format '%e %w%f' "$WORKDIR" |
        while read -r events filepath; do
            case "$events" in
                *ISDIR*) continue ;;
            esac

            log "Datei '$filepath' erkannt ($events)"
            throttle
            process_file "$filepath" &
        done &
    wait $!

    log "inotifywait wurde beendet, die Überwachung wird neu gestartet."
    sleep 5 &
    wait $!
done

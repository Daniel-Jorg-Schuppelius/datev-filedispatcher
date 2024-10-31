#!/bin/bash

# Arbeitsverzeichnisse für den Dispatcher
CODEDIR="$(dirname "$0")"
WORKDIR="/opt/internal_dispatcher/"
DISPATCHER="$CODEDIR/../src/DatevFileDispatcher.php"

# Funktion zur Überprüfung, ob ein Befehl verfügbar ist
check_command() {
    if ! command -v "$1" &> /dev/null; then
        echo "Fehler: $1 ist nicht installiert. Bitte installieren Sie $1, bevor Sie fortfahren." >&2
        exit 1
    fi
}

# Überprüfung auf benötigte Abhängigkeiten
check_command inotifywait
check_command logger
check_command php

# Ins Arbeitsverzeichnis wechseln
cd "$WORKDIR" || { echo "Fehler: Konnte nicht ins Arbeitsverzeichnis $WORKDIR wechseln."; exit 1; }

# Logging starten
exec 1> >(logger -s -t "$(basename "$0")" 2>&1)
exec 2> >(logger -s -t "$(basename "$0")")

logger -s -t "$(basename "$0")" "Starting filedispatcher"
logger -s -t "$(basename "$0")" "Workdir: $WORKDIR"

# Signal zum Beenden einrichten
trap 'quit=1' USR1

umask 0000
quit=0
while [ "$quit" -ne 1 ]; do
    # Überwache das Arbeitsverzeichnis auf neue oder geänderte Dateien
    inotifywait --monitor --syslog --quiet --recursive \
                --event create --event moved_to --event close_write --event move_self \
                --exclude '(log\.txt)' "$WORKDIR" |
    while read -r path action filename; do
        logger -s -t "$(basename "$0")" "The file '$filename' appeared in directory '$path' via '$action'"
        logger -s -t "$(basename "$0")" "Starting $DISPATCHER1 $path$filename"
        
        # Überprüfen, ob die Datei existiert und ausführbar ist
        if [ -f "$path$filename" ]; then
            sleep 1  # Kurze Pause, um sicherzustellen, dass die Datei vollständig geschrieben wurde
            php "$DISPATCHER" "$path$filename"  # Dispatcher ausführen
        else
            logger -s -t "$(basename "$0")" "Fehler: Die Datei $path$filename existiert nicht oder ist nicht lesbar."
        fi
    done
done

echo "Das USR1-Signal wurde empfangen und verarbeitet."

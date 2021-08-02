#!/bin/bash
cd "$(dirname "$0")"
if [ ! -d "${1}" ]; then
	echo "dossier ${1} inexistant"
	exit 1
fi
[ ! -d "${1}/LogsKofaxToMC" ] && mkdir "${1}/LogsKofaxToMC"
logFile="${1}/LogsKofaxToMC/kofax_capture_$(date +"%Y%m%d-%H%M%S").log"
time php php/main.php config.xml 2>&1 | tee "${logFile}"
[ "$(wc -l < "${logFile}")" -eq 1 ] && rm -f "${logFile}"

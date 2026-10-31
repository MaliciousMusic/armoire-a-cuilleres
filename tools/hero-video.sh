#!/usr/bin/env sh
# Le film du hero : des plans choisis dans une vidéo source, mis bout à bout, sans le son, en boucle.
# Le site le prend tout seul dès que www/assets/video/hero.mp4 existe (et hero.webm s'il est là).
#
#   sh tools/hero-video.sh <vidéo source> <début-fin> [<début-fin> …]
#   ex. sh tools/hero-video.sh ~/Desktop/rushes.mp4 0:03-0:06 0:12.5-0:16 0:31-0:35
#
# Chaque plan est recadré pour remplir un écran large (16:9), sans bande noire ; le son est retiré.
set -e
[ $# -ge 2 ] || { echo "usage : sh tools/hero-video.sh <vidéo source> <début-fin> [<début-fin> …]"; exit 1; }
SRC="$1"; shift
cd "$(dirname "$0")/.."
OUT=www/assets/video
TMP="$OUT/.plans"   # dans le projet : ffmpeg (Windows) ne lit pas les chemins /tmp de Git Bash
rm -rf "$TMP"; mkdir -p "$TMP"
sec() { echo "$1" | awk -F: '{ s = 0; for (i = 1; i <= NF; i++) s = s * 60 + $i; print s }'; }

i=0
for seg in "$@"; do
  a=$(sec "${seg%-*}"); b=$(sec "${seg#*-}")
  i=$((i + 1))
  # recadrage plein écran 1920×1080, 25 images/s, sans le son
  ffmpeg -loglevel error -y -ss "$a" -to "$b" -i "$SRC" -an \
    -vf "scale=1920:1080:force_original_aspect_ratio=increase,crop=1920:1080,fps=25,format=yuv420p" \
    -c:v libx264 -preset slow -crf 20 "$TMP/plan$i.mp4"
  echo "file 'plan$i.mp4'" >> "$TMP/liste.txt"
done

ffmpeg -loglevel error -y -f concat -safe 0 -i "$TMP/liste.txt" -an -c:v libx264 -preset slow -crf 23 -movflags +faststart "$OUT/hero.mp4"
ffmpeg -loglevel error -y -i "$OUT/hero.mp4" -an -c:v libvpx-vp9 -b:v 0 -crf 36 -row-mt 1 "$OUT/hero.webm"
# l'image affichée pendant le chargement : la première image du film
ffmpeg -loglevel error -y -i "$OUT/hero.mp4" -frames:v 1 -vf "scale=1280:-2" "$OUT/hero-poster.webp"
rm -rf "$TMP"
ls -la "$OUT"

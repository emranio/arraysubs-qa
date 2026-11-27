#!/bin/bash
# Contact sheets for reviewing a finished video: one frame every STEP seconds, 4x3 tiles per sheet.
# Usage: ./sheet.sh <video.mp4> [step_seconds=4] [outdir=<video dir>/sheets]
set -e
VID="$1"; STEP="${2:-4}"; OUT="${3:-$(dirname "$VID")/sheets}"
mkdir -p "$OUT"; rm -f "$OUT"/sheet-*.jpg
ffmpeg -loglevel error -y -i "$VID" -vf "fps=1/${STEP},scale=640:-1,drawbox=x=0:y=0:w=iw:h=ih:color=white@0.0,tile=3x4:padding=6:color=white" -q:v 3 "$OUT/sheet-%02d.jpg"
ls "$OUT"/sheet-*.jpg

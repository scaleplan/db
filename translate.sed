#!/usr/bin/sed -f

s/] (/](/g
s/ \/ /\//g
s/ \\ /\\/g
s/& mdash/\&mdash/g
s/(# /(#/g
s/Класс ошибки/Error class/g
s/\(\*\**\)\s\(.*\)\s\(\*\**\)/\1\2\3/g
s/$ /$/g
s/ ()/()/g
s/#__ /#__/g
s/` /`/g
s/- `\$/\t- `$/g
s/- \[`/  - [`/g
s/<\/\s*\(.*\)\s*>/<\/\1>/g
s/docs_ru/docs_en/g
s/The <br>/<br>/g
s/"`/```/g

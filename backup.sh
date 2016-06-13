#!/bin/bash
tar -czvf /var/www/spartacuswallpaper.com/var/spartacus-$(date +%Y%m%d).sqlite.tgz /var/www/spartacuswallpaper.com/spartacus
cp /var/www/spartacuswallpaper.com/var/spartacus-$(date +%Y%m%d).sqlite.tgz /var/www/spartacuswallpaper.com/var/spartacus-$(date +%Y%m).sqlite.tgz
d=$(date -I -d "$d - 10 day")
rm /var/www/spartacuswallpaper.com/var/spartacus-$(date -d "$d" +%Y%m%d).sqlite.tgz

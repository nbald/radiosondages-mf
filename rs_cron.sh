#!/bin/bash

function process_station {
  RUN=$1
  STATION=$2

  FILE=$STATION.$RUN;
  URL="https://donneespubliques.meteofrance.fr/donnees_libres/Pdf/RS/$FILE.pdf";

  for i in {0..20}; do
    wget --spider -q $URL && break
    sleep 60
  done

  wget -q $URL
  if [ "$?" != "0" ]; then
    echo "$STATION wget failed"
    return
  fi

  inkscape --without-gui --file=$FILE.pdf --export-plain-svg=/tmp/$FILE.svg
  php /opt/mf/rs/rs.php $RUN $STATION
  rm /tmp/$FILE.svg
  echo $STATION ok

}

RUN=$(date --date="utc - 5 hour"  +%Y%m%d%H)
DIR="${RUN:0:6}/$RUN"

cd /var/www/labo/mf/radiosondages/

mkdir -p $DIR
cd $DIR

for STATION in $(cat /opt/mf/rs/stations.txt); do
  process_station $RUN $STATION &
  sleep 5;
done

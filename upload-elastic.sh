#!/bin/sh

echo 'bibliography-10000.json'
curl http://user:7WbQZedlAvzQ@35.204.73.93/elasticsearch/ala/_bulk -H 'Content-Type: application/x-ndjson' -XPOST --data-binary '@bibliography-10000.json'  --progress-bar | tee /dev/null
echo ''
echo 'bibliography-20000.json'
curl http://user:7WbQZedlAvzQ@35.204.73.93/elasticsearch/ala/_bulk -H 'Content-Type: application/x-ndjson' -XPOST --data-binary '@bibliography-20000.json'  --progress-bar | tee /dev/null
echo ''
echo 'bibliography-30000.json'
curl http://user:7WbQZedlAvzQ@35.204.73.93/elasticsearch/ala/_bulk -H 'Content-Type: application/x-ndjson' -XPOST --data-binary '@bibliography-30000.json'  --progress-bar | tee /dev/null
echo ''
echo 'bibliography-40000.json'
curl http://user:7WbQZedlAvzQ@35.204.73.93/elasticsearch/ala/_bulk -H 'Content-Type: application/x-ndjson' -XPOST --data-binary '@bibliography-40000.json'  --progress-bar | tee /dev/null
echo ''
echo 'bibliography-50000.json'
curl http://user:7WbQZedlAvzQ@35.204.73.93/elasticsearch/ala/_bulk -H 'Content-Type: application/x-ndjson' -XPOST --data-binary '@bibliography-50000.json'  --progress-bar | tee /dev/null
echo ''
echo 'bibliography-60000.json'
curl http://user:7WbQZedlAvzQ@35.204.73.93/elasticsearch/ala/_bulk -H 'Content-Type: application/x-ndjson' -XPOST --data-binary '@bibliography-60000.json'  --progress-bar | tee /dev/null
echo ''
echo 'bibliography-70000.json'
curl http://user:7WbQZedlAvzQ@35.204.73.93/elasticsearch/ala/_bulk -H 'Content-Type: application/x-ndjson' -XPOST --data-binary '@bibliography-70000.json'  --progress-bar | tee /dev/null
echo ''
echo 'bibliography-80000.json'
curl http://user:7WbQZedlAvzQ@35.204.73.93/elasticsearch/ala/_bulk -H 'Content-Type: application/x-ndjson' -XPOST --data-binary '@bibliography-80000.json'  --progress-bar | tee /dev/null
echo ''
echo 'bibliography-85008.json'
curl http://user:7WbQZedlAvzQ@35.204.73.93/elasticsearch/ala/_bulk -H 'Content-Type: application/x-ndjson' -XPOST --data-binary '@bibliography-85008.json'  --progress-bar | tee /dev/null
echo ''

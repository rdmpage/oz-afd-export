#!/bin/sh

echo 'hasName-0.nt'
curl http://130.209.46.63/blazegraph/sparql?context-uri=https://biodiversity.org.au/afd/publication -H 'Content-Type: text/rdf+n3' --data-binary '@hasName-0.nt'  --progress-bar | tee /dev/null
echo ''
sleep 5

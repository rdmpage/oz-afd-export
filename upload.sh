#!/bin/sh

echo 'biblio-0.nt'
curl http://130.209.46.63/blazegraph/sparql?context-uri=https://biodiversity.org.au/afd/publication -H 'Content-Type: text/rdf+n3' --data-binary '@biblio-0.nt'  --progress-bar | tee /dev/null
echo ''
sleep 5
echo 'biblio-500000.nt'
curl http://130.209.46.63/blazegraph/sparql?context-uri=https://biodiversity.org.au/afd/publication -H 'Content-Type: text/rdf+n3' --data-binary '@biblio-500000.nt'  --progress-bar | tee /dev/null
echo ''
sleep 5
echo 'biblio-1000000.nt'
curl http://130.209.46.63/blazegraph/sparql?context-uri=https://biodiversity.org.au/afd/publication -H 'Content-Type: text/rdf+n3' --data-binary '@biblio-1000000.nt'  --progress-bar | tee /dev/null
echo ''
sleep 5
echo 'biblio-1500000.nt'
curl http://130.209.46.63/blazegraph/sparql?context-uri=https://biodiversity.org.au/afd/publication -H 'Content-Type: text/rdf+n3' --data-binary '@biblio-1500000.nt'  --progress-bar | tee /dev/null
echo ''
sleep 5
echo 'biblio-2000000.nt'
curl http://130.209.46.63/blazegraph/sparql?context-uri=https://biodiversity.org.au/afd/publication -H 'Content-Type: text/rdf+n3' --data-binary '@biblio-2000000.nt'  --progress-bar | tee /dev/null
echo ''
sleep 5

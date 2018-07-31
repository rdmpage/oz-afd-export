#!/bin/sh


echo 'names-1810000.nt'
curl http://130.209.46.63/blazegraph/sparql?context-uri=https://biodiversity.org.au/afd/publication -H 'Content-Type: text/rdf+n3' --data-binary '@names-1810000.nt'
echo ''
sleep 10
echo 'names-2120000.nt'
curl http://130.209.46.63/blazegraph/sparql?context-uri=https://biodiversity.org.au/afd/publication -H 'Content-Type: text/rdf+n3' --data-binary '@names-2120000.nt'
echo ''
sleep 10
echo 'names-2120000.nt'
curl http://130.209.46.63/blazegraph/sparql?context-uri=https://biodiversity.org.au/afd/publication -H 'Content-Type: text/rdf+n3' --data-binary '@names-2120000.nt'
echo ''
sleep 10
echo 'names-2430000.nt'
curl http://130.209.46.63/blazegraph/sparql?context-uri=https://biodiversity.org.au/afd/publication -H 'Content-Type: text/rdf+n3' --data-binary '@names-2430000.nt'
echo ''
sleep 10
echo 'names-2430000.nt'
curl http://130.209.46.63/blazegraph/sparql?context-uri=https://biodiversity.org.au/afd/publication -H 'Content-Type: text/rdf+n3' --data-binary '@names-2430000.nt'
echo ''
sleep 10
echo 'names-2440000.nt'
curl http://130.209.46.63/blazegraph/sparql?context-uri=https://biodiversity.org.au/afd/publication -H 'Content-Type: text/rdf+n3' --data-binary '@names-2440000.nt'
echo ''
sleep 10

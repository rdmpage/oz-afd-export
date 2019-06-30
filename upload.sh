#!/bin/sh

echo 'bibliography-0.nt'
curl http://kg-blazegraph.sloppy.zone/blazegraph/sparql?context-uri=https://biodiversity.org.au/afd/publication -H 'Content-Type: text/rdf+n3' --data-binary '@bibliography-0.nt'  --progress-bar | tee /dev/null
echo ''
sleep 5
echo 'bibliography-500000.nt'
curl http://kg-blazegraph.sloppy.zone/blazegraph/sparql?context-uri=https://biodiversity.org.au/afd/publication -H 'Content-Type: text/rdf+n3' --data-binary '@bibliography-500000.nt'  --progress-bar | tee /dev/null
echo ''
sleep 5
echo 'bibliography-1000000.nt'
curl http://kg-blazegraph.sloppy.zone/blazegraph/sparql?context-uri=https://biodiversity.org.au/afd/publication -H 'Content-Type: text/rdf+n3' --data-binary '@bibliography-1000000.nt'  --progress-bar | tee /dev/null
echo ''
sleep 5
echo 'bibliography-1500000.nt'
curl http://kg-blazegraph.sloppy.zone/blazegraph/sparql?context-uri=https://biodiversity.org.au/afd/publication -H 'Content-Type: text/rdf+n3' --data-binary '@bibliography-1500000.nt'  --progress-bar | tee /dev/null
echo ''
sleep 5
echo 'bibliography-2000000.nt'
curl http://kg-blazegraph.sloppy.zone/blazegraph/sparql?context-uri=https://biodiversity.org.au/afd/publication -H 'Content-Type: text/rdf+n3' --data-binary '@bibliography-2000000.nt'  --progress-bar | tee /dev/null
echo ''
sleep 5
echo 'bibliography-2500000.nt'
curl http://kg-blazegraph.sloppy.zone/blazegraph/sparql?context-uri=https://biodiversity.org.au/afd/publication -H 'Content-Type: text/rdf+n3' --data-binary '@bibliography-2500000.nt'  --progress-bar | tee /dev/null
echo ''
sleep 5
echo 'bibliography-3000000.nt'
curl http://kg-blazegraph.sloppy.zone/blazegraph/sparql?context-uri=https://biodiversity.org.au/afd/publication -H 'Content-Type: text/rdf+n3' --data-binary '@bibliography-3000000.nt'  --progress-bar | tee /dev/null
echo ''
sleep 5
echo 'bibliography-3500000.nt'
curl http://kg-blazegraph.sloppy.zone/blazegraph/sparql?context-uri=https://biodiversity.org.au/afd/publication -H 'Content-Type: text/rdf+n3' --data-binary '@bibliography-3500000.nt'  --progress-bar | tee /dev/null
echo ''
sleep 5
echo 'bibliography-4000000.nt'
curl http://kg-blazegraph.sloppy.zone/blazegraph/sparql?context-uri=https://biodiversity.org.au/afd/publication -H 'Content-Type: text/rdf+n3' --data-binary '@bibliography-4000000.nt'  --progress-bar | tee /dev/null
echo ''
sleep 5

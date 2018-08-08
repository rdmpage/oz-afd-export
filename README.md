# oz-afd-export
Export AFD data for Elasticsearch, RDF, etc.

## Gotchas

### DOI redirects

DOI https://doi.org/10.11646/zootaxa.3735.3.1 redirects to https://doi.org/10.11646/zootaxa.3745.3.1, so if we map to 3735.3.1 we miss the links to 3745.3.1 :( Looks like at some point Zootaxa decided the DOI was wrong and changed it, but old one kept and resolves to a 301 Moved Permanently

## PDFs

Display PDFs if they are “free” or explicitly open access, otherwise just thumbnails. Could use http://schema.org/isAccessibleForFree to flag status of PDF, note that this is a boolean value, so in JSON-LD looks like this:

```
{
   "isAccessibleForFree": false
}
```

but in triples like this:

```
_:b0 <http://schema.org/isAccessibleForFree> "false"^^<http://www.w3.org/2001/XMLSchema#boolean> .
```

```
UPDATE bibliography SET `free`='Y' WHERE `PUB_PARENT_JOURNAL_TITLE` = 'Linzer Biologische Beiträge' AND `pdf` IS NOT NULL;
```



## RDF

### Blazegraph

Use CURL to upload sets of triples, use script ```blazegraph/load-chunked-rdf``` . Big triples files are chunked into subsets using ```chunk2.php```. Note also the importance of MIME type, triples MUST be sent to blazegraph as ```text/rdf+n3``` to preserve UTF-8 encoding.

To upload triples:

```
curl http://localhost:9999/blazegraph/sparql -H 'Content-Type: text/rdf+n3' --data-binary '@bibliography.nt'
```

To named graph:
```
curl http://127.0.0.1:9999/blazegraph/sparql?context-uri=https://biodiversity.org.au/afd/publication -H 'Content-Type: text/rdf+n3' --data-binary ‘@bibliography’
```

To names graph and progress bar:
```
curl http://130.209.46.63/blazegraph/sparql?context-uri=https://biodiversity.org.au/afd/publication -H 'Content-Type: text/rdf+n3' --data-binary '@thumbnail.nt'  --progress-bar | tee /dev/null
```

## CouchDB

Use CouchDB to cache data, and also post-process to generate triples, etc.

### Lists

Lists are a feature of CouchDB we can use to dump all the triples in a view. It is faster than querying the view itself as we avoid paging through the rows, which is slow.

Here is a simple list to output triples:
```
"lists": {
    "triples": "function(head,req) { var row; start({ 'headers': { 'Content-Type': 'text/plain' } }); while(row = getRow()) { send(row.value); } }"
  }
```

It simply takes a row from the view and dumps it as a line of plain text. To use this, do the following:

```<couchdb server>:5984/<database>/_design/<design-name>/_list/triples/<view-name>```

For example, 

```http://127.0.0.1:5984/oz-csl/_design/crossref/_list/triples/citation-nt```

Call this using ```curl``` to generate a file of triples, e.g.:

```
curl http://127.0.0.1:5984/oz-csl/_design/crossref/_list/triples/citation-nt > csl.nt```

Taxa: 

curl http://127.0.0.1:5984/ala/_design/nt/_list/triples_nt/triples > taxa.nt



## Elasticsearch

### Basic idea

Take data and convert to a schema (see https://project-a.github.io/on-site-search-design-patterns-for-e-commerce/ ) that enables us to specify what we want to search on, and some basic fields we want to display in the results. This means we can standardise the data, and only include things that are relevant to search and displaying results. Can implement in various ways, such as PHP script to query MySQL database, or CouchDB view, depending on where the data is stored.

### Document ids

Use local GUIDs for document ids, e.g. UUID for publication is Elasticsearch document id. We also store URL identifier in the document itself.

### Index types and ES6

Elasticsearch 6 only supports one type of document per index (see e.g., https://github.com/elastic/elasticsearch-rails/issues/779), preferred type name is ```_doc```, see https://www.elastic.co/guide/en/elasticsearch/reference/current/removal-of-types.html#_schedule_for_removal_of_mapping_types

### PUT versus POST

First upload uses PUT, we can subsequently update the records (for example, if we want to change the data we store) using POST to the document id and append ```_update``` to the URL.

### Bitnami

Bitnami has version 6.3.1. Remember to create the index before uploading data. Simply PUT http://35.204.73.93/elasticsearch/ala to create index. Note that Bitnami also needs authentication, so we need user and password for each call.

### Kitematic

Local Docker version is 5.6.9 which doesn’t need authentication.

### Bulk upload

Use _bulk endpoint to upload many records at once.


## Zenodo

### Gotchas

Zenodo records can be updated, and looks like Plazi has done this a lot, which means metadata in local CouchDB can be out of date. Need to refresh tis [to do].

### Uploading

Several steps needed to link to Zenodo.

#### Match publication to Zenodo

Use script ```zenodo-match.php``` to match ```[journal,volume,spage]``` triple to Zenodo, using CouchDB version of BLR. This gives us the Zenodo record id for a publication.

#### Get parts (figures) for each Zenodo record

Use script ```zenodo-fetch-parts.php``` to get array of Zenodo record ids for figures for a given Zenodo record id, store these as a JSON array.

#### Resolve Zenodo ids as JSON-LD

Use script ```zenodo-get-figures.php``` to fetch JSON-LD for each part and convert to triples. Upload this to triple store and we can then query for figures.

#### Upload to triple store

Use named graph:

```
curl http://130.209.46.63/blazegraph/sparql?context-uri=https://zenodo.org -H 'Content-Type: text/rdf+n3' --data-binary '@junk/z.nt'  --progress-bar | tee /dev/null
```




# oz-afd-export
Export AFD data for Elasticsearch, RDF, etc.


## RDF


## CouchDB

Use CouchDB to cache data, and also post-process to generate triples, etc.

### Lists

Lists are a feature of CocuhDB we can use to dump all the triples in a view. It is faster than querying the view itself as we avoid paging through the rows, which is slow.

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

# 🎼 Trackloaded Artist Dataset

This repository contains the **Trackloaded Artist Dataset**, an open dataset providing structured linked data about Nigerian music artists featured on [Trackloaded](https://trackloaded.com/).

The dataset includes:
- Artist names
- Profile pages
- Biographies
- Birth dates
- Social media and external identity links (Wikidata, instagram and more.)

The data is published in **RDF** formats and is queryable via a public **SPARQL endpoint**.

---

## 📂 Files in this repository
- **`void.ttl`** – VoID (Vocabulary of Interlinked Datasets) metadata describing the dataset  
- **`README.md`** – This file  
- **`LICENSE`** – Creative Commons Attribution 4.0 International License

---

## 🔗 Access Points
- **Dataset Homepage:** [https://trackloaded.com/data](https://trackloaded.com/data)  
- **SPARQL Endpoint:** [https://trackloaded.com/sparql-endpoint.php](https://trackloaded.com/sparql-endpoint.php)  
- **SPARQL Browser UI:** [https://trackloaded.com/sparql-browser](https://trackloaded.com/sparql-browser)  
- **VoID Metadata:** [https://trackloaded.com/?void=1](https://trackloaded.com/?void=1)  
- **RDF Sitemap (Turtle):** [https://trackloaded.com/?build_rdf_sitemap](https://trackloaded.com/?build_rdf_sitemap)  

---

## 📄 Per-Artist RDF
Each artist profile page supports RDF output via query parameters:

- `?rdf=ttl` or `?format=ttl` → Turtle format  
- `?rdf=rdf` or `?format=rdf` → RDF/XML format  
- `?rdf=xml` or `?format=xml` → RDF/XML format  

**Example:**  
- [https://trackloaded.com/tag/olamide/?rdf=ttl](https://trackloaded.com/tag/olamide/?rdf=ttl)  
- [https://trackloaded.com/tag/olamide/?format=ttl](https://trackloaded.com/tag/olamide/?format=ttl)

---

## Sample SPARQL Query
```sparql
SELECT ?name ?url ?bio
WHERE {
  ?x foaf:name ?name ;
     foaf:homepage ?url .
  OPTIONAL { ?x foaf:bio ?bio }
}


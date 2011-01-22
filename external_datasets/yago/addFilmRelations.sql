insert into dbpedia_yago.facts (id, relation, arg1, arg2, weight)
SELECT NULL, 'subClassOf', concat('wikicategory_', page_title), 'wordnet_motion_picture_film_103789400', 1 FROM dbpedia_en.page p where page_namespace = 14 and page_title like '%films';

insert into dbpedia_yago.facts (id, relation, arg1, arg2, weight)
SELECT NULL, 'subClassOf', concat('wikicategory_', page_title), 'wordnet_motion_picture_film_103789400', 1 FROM dbpedia_en.page p where page_namespace = 14 and page_title like 'Films%';

insert into dbpedia_yago.facts (id, relation, arg1, arg2, weight)
SELECT NULL, 'subClassOf', concat('wikicategory_', page_title), 'wordnet_motion_picture_film_103789400', 1 FROM dbpedia_en.page p where page_namespace = 14 and page_title like '%Films';
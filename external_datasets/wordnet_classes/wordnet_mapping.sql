-- phpMyAdmin SQL Dump
-- version 2.9.0.1
-- http://www.phpmyadmin.net
-- 
-- Host: localhost
-- Erstellungszeit: 24. Oktober 2007 um 02:10
-- Server Version: 5.0.24
-- PHP-Version: 5.1.6
-- 
-- Datenbank: `dbpedia_develop`
-- 

-- --------------------------------------------------------

-- 
-- Tabellenstruktur für Tabelle `wordnet_mapping`
-- 

CREATE TABLE `wordnet_mapping` (
  `infobox` varchar(255) NOT NULL,
  `ID1` bigint(20) NOT NULL,
  `ID2` bigint(20) default NULL,
  `label` varchar(255) NOT NULL,
  `label2` varchar(255) default NULL,
  `wordnetURI` varchar(255) default NULL,
  PRIMARY KEY  (`infobox`,`ID1`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- 
-- Daten für Tabelle `wordnet_mapping`
-- 

INSERT INTO `wordnet_mapping` VALUES ('Infobox_hrhstyles', 301592509, 0, 'royal', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_mineral', 114662574, 0, 'mineral\n', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_Disease', 114070360, 0, 'disease', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('infobox_hurricane_small', 111467018, 0, 'hurricane', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_Dogbreed', 111467018, 0, 'hurricane', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_Writer', 110794014, 0, 'writer, author', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_Wrestler', 110793168, 0, 'wrestler,grappler,matman', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_Tennis_player', 110701180, 0, 'tennisplayer', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_Monarch', 110628644, 0, 'sovereign, crowned head, monarch', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Football_player_infobox', 110618342, 0, 'soccer player', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_Football_biography', 110618342, 0, 'soccerplayer', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_Senator', 110578471, 0, 'senator', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_Scientist', 110560637, 0, 'scientist, man of science', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_Saint', 110546850, 0, 'saint, holy man, holy person, angel', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_NASCAR_driver', 110502576, 0, 'racer, race driver, automobile driver', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_President', 110467179, 0, 'president', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_Pope', 110453533, 0, '', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_Politician', 110450303, 0, 'politician, politico, pol, political leader', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_Indian_politician', 110450303, 0, 'politician, politico, pol, political leader', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_US_Cabinet_official', 110450303, 0, 'politician, politico, pol, political leader', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_rugby_league_biography', 110439851, 109820263, 'player, participant', 'athlete, jock', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_Go_player', 110439851, 0, 'player, participant', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Rugby_league_player_infobox2', 110439851, 109820263, 'player, participant', 'athlete, jock', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_Snooker_player', 110439851, 0, 'player, participant', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_Rugby_biography', 110439851, 109820263, 'player, participant', 'athlete, jock', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_Philosopher', 110423589, 0, 'philosopher', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_musical_artist', 110340312, 0, 'musician,instrumentalist,player', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_musical_artist_2', 110340312, 0, 'musician,instrumentalist,player', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_Playboy_Playmate', 110324560, 106258361, 'model, poser', 'centerfold, centrefold', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_Officeholder', 110202624, 0, 'incumbent, officeholder', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_Ice_Hockey_Player', 110179291, 0, 'hockeyplayer,ice-hockeyplayer', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Guitarist_infobox', 110151760, 0, 'guitarist, guitar player', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_Governor', 110140314, 0, 'governor', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_afl_player', 110101634, 0, 'footballplayer,footballer', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_NFL_player', 110101634, 0, 'footballplayer,footballer', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_Cricketer', 109977326, 0, 'cricketer', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_Historic_Cricketer', 109977326, 0, 'cricketer', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_Old_Cricketer', 109977326, 0, 'cricketer', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Cricketer_Infobox', 109977326, 0, 'cricketer', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_Recent_cricketer', 109977326, 0, 'cricketer', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_Cricketer_(Career)', 109977326, 0, 'cricketer', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_Congressman', 109955781, 0, 'congressman, congresswoman, representative', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_Prime_Minister', 109906986, 0, 'chancellor, premier, prime minister', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_Celebrity', 109903153, 0, 'celebrity, famous person', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('infobox_poker_player', 109894654, 0, 'card player', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_Boxer', 109870208, 0, 'boxer,pugilist', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_NBA_Player', 109842047, 0, 'basketball player, basketeer, cager', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_MLB_player', 109835506, 0, 'ballplayer, baseball player', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_GAA_player', 109820263, 0, 'athlete, jock', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_NCAA_Athlete', 109820263, 0, 'athlete, jock', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_Lacrosse_Player', 109820263, 0, 'athlete, jock', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_Astronaut', 109818022, 0, 'astronaut, spaceman, cosmonaut', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_Comics_creator', 109812338, 110292316, 'artist, creative person', 'manufacturer, producer', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_Artist', 109812338, 0, 'artist, creative person', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_Architect', 109805475, 0, 'architect, designer', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_Actor', 109765278, 0, 'actor,histrion,player,thespian,roleplayer', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_actor_voice', 109765278, 107110615, 'actor, histrion, player, thespian, role player', 'voice, vocalization, vocalisation, phonation, vox', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_D&D_creature', 109587565, 0, 'fictional character, fictitious character, character', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_river', 109411430, 0, 'river', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_Planet', 109394646, 0, 'planet', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_Mountain_Pass', 109386842, 0, 'pass, mountain pass, notch', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_Mountain', 109359803, 0, 'mountain,mount', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_lake', 109328904, 0, 'lake', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_Glacier', 109289331, 0, 'glacier\n', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_Comet', 109251407, 0, 'comet', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_Township', 108672199, 0, 'township, town', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_Kommune', 108672199, 0, 'township, town', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_Belgium_Municipality', 108672199, 108225581, 'township, town', 'municipality', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_Municipality_pt', 108672199, 108225581, 'township, town', 'municipality', NULL);
INSERT INTO `wordnet_mapping` VALUES ('infobox_Scotland_place', 108672199, 0, 'township, town', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_ProvinceIT', 108654360, 0, 'state, province', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_protected_area', 108600992, 0, 'national park', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_World_Heritage_Site', 108600443, 0, 'monument', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_Amphoe', 108552138, 0, 'district, territory, territorial dominion, dominion', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_of_upazilas', 108552138, 0, 'district, territory, territorial dominion, dominion', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_German_district', 108552138, 0, 'district, territory, territorial dominion, dominion', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_Kommun', 108552138, 0, 'district, territory, territorial dominion, dominion', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_German_district_noregbez', 108552138, 0, 'district, territory, territorial dominion, dominion', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_Austrian_district', 108552138, 0, 'district, territory, territorial dominion, dominion', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_U.S._County', 108546870, 0, 'county', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('US_County_infobox', 108546870, 0, 'county\n', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('infoboxneeded', 108546870, 0, 'county', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_Luxembourg_commune', 108541609, 0, 'commune', '', 'http://www.w3.org/2006/03/wn/wn20/instances/synset-Kansas_City-noun-1');
INSERT INTO `wordnet_mapping` VALUES ('Infobox_Amusement_park', 108494231, 0, 'amusement park, funfair, pleasure ground', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_Secondary_school', 108276720, 0, 'school', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_Education_in_Canada', 108276720, 0, 'school', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_School', 108276720, 0, 'school', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_School2', 108276720, 0, 'school', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_Private_School', 108276720, 0, 'school', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_Aust_school', 108276720, 0, 'school', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_Political_Party', 108256968, 0, 'party, political party', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_Canada_Political_Party', 108256968, 0, 'party, political party', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_Band', 108249960, 0, 'danceband,band,danceorchestra', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_Union', 108233056, 0, 'union, labor union, trade union, trades union, brotherhood', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_sports_league', 108231184, 0, 'league, conference', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_aus_sport_club', 108227214, 0, 'club, society, guild, gild, lodge, order', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_CityIT', 108226335, 0, 'city,metropolis', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_Indian_urban_area', 108226335, 108672199, 'city,metropolis', 'township, town', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_City', 108226335, 0, 'city,metropolis', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_Swiss_town', 108226335, 0, 'city,metropolis', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_Town_DE', 108226335, 0, 'city,metropolis', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_City_in_Romania', 108226335, 0, 'city,metropolis', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_Town_GR', 108226335, 0, 'city,metropolis', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('infobox_city_spain', 108226335, 0, 'city,metropolis', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_Poland', 108226335, 0, 'city,metropolis', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_German_Location', 108226335, 0, 'city,metropolis', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_Slovak_town', 108226335, 0, 'city,metropolis', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_town_TR', 108226335, 0, 'city,metropolis', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('infobox_Lithuanian_city', 108226335, 108672199, 'city,metropolis', 'township, town', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_Philippine_city', 108226335, 0, 'city,metropolis', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_Parish_pt', 108225581, 108672199, 'municipality', 'township, town', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_UK_Police', 108209687, 0, 'police, police force, constabulary, law', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_Wrestling_team', 108208560, 0, 'team, squad', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_Military_Unit', 108189659, 0, 'unit, social unit', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_Hurricane', 108101410, 102084071, 'breed, strain, stock', 'dog, domestic dog, Canis familiaris', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Football_club_infobox', 108079613, 0, 'baseball club, ball club, club, nine', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_Football_club', 108079613, 0, 'baseballclub,ballclub,club,nine', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_Newspaper', 108062970, 0, 'newspaper, paper, newspaper publisher', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_Company', 108058098, 0, 'company', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_Hospital', 108054417, 0, 'hospital', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_Class', 107997703, 104552696, 'class, category, family', 'warship, war vessel, combat ship', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_Wrestling_event', 107464725, 100447540, 'tournament, tourney', 'wrestling, rassling, grappling', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Song_infobox', 107048000, 0, 'song', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('infobox_ESC_entry', 107048000, 0, 'song', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_song', 107048000, 0, 'song', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_Musical', 107019172, 0, 'musical, musical comedy, musical theater', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_Musical_2', 107019172, 0, 'musical, musical comedy, musical theater', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_programming_language', 106898352, 0, 'programming language, programing language', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('infobox_record_label', 106851516, 0, 'label, recording label', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_font', 106825399, 0, 'font, fount, typeface, face', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_file_format', 106636806, 0, 'format, formatting, data format, data formatting', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_television', 106622595, 0, 'telecast', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_Anime_episode', 106621771, 106616464, 'episode, installment, instalment', 'cartoon, animated cartoon', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_Simpsons_episode', 106621771, 106616464, 'episode, installment, instalment', 'cartoon, animated cartoon', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_Star_Trek_episode', 106621771, 106621447, 'episode, installment, instalment', 'serial,series', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_Dad''s_Army', 106621771, 0, 'episode, installment, instalment', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_Television_episode', 106621447, 0, 'serial,series', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_Family_Guy_episode', 106621447, 106621771, 'serial,series', 'episode, installment, instalment', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_animanga/Header', 106616464, 0, 'cartoon, animated cartoon', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_animanga/Manga', 106616464, 0, 'cartoon, animated cartoon', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_animanga/Anime', 106616464, 0, 'cartoon, animated cartoon', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_animanga/OVA', 106616464, 0, 'cartoon, animated cartoon', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_animanga/Movie', 106616464, 0, 'cartoon, animated cartoon', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_Hollywood_cartoon', 106616464, 0, 'cartoon, animated cartoon', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_South_Park_episode', 106616464, 106621771, 'cartoon, animated cartoon', 'episode, installment, instalment', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_Film', 106613686, 0, 'movie, film, picture, moving picture, moving-picture show, motion picture, motion-picture show, picture show, pic, flick', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_movie', 106613686, 0, 'movie, film, picture, moving picture, moving-picture show, motion picture, motion-picture show, picture show, pic, flick', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_Journal', 106597466, 0, 'journal', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_Magazine', 106595351, 0, 'magazine, mag', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Album_infobox', 106591815, 0, 'album,recordalbum', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_OS', 106568134, 0, 'operatingsystem,OS', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_Software', 106566077, 0, 'software,softwaresystem,softwarepackage,package', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_Software2', 106566077, 0, 'software,softwaresystem,softwarepackage,package', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_Biography', 106515827, 0, 'biography,life,lifestory,lifehistory', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_short_story', 106371999, 0, 'shortstory\n', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_Website', 106359193, 0, 'web site, internet site, site', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_Language', 106282651, 0, 'language,linguisticcommunication', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_character', 105929008, 0, 'character, role, theatrical role, part, persona', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_Brain', 105481095, 105217168, 'brain, encephalon', 'human body, physical body, material body, soma, build, figure, physique, anatomy, shape, bod, chassis, frame, form, flesh', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_Nerve', 105474346, 0, 'nerve, nervus', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_Vein', 105418717, 0, 'vein, vena, venous blood vessel', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Muscle_infobox', 105289297, 0, 'muscle, musculus', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_Muscle', 105289297, 0, 'muscle, musculus', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_Bone', 105269901, 0, 'bone, os', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_Anatomy', 105217168, 0, 'human body, physical body, material body, soma, build, figure, physique, anatomy, shape, bod, chassis, frame, form, flesh', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('infobox_color', 104956594, 0, 'color,colour,coloring,colouring', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_Weapon', 104565375, 0, 'weapon,arm,weaponsystem', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_University', 104511002, 0, 'university', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_Mobile', 104401088, 0, 'telephone, phone, telephone set', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_synthesizer', 104376400, 0, 'synthesizer, synthesiser', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_Paris_metro', 104349077, 0, 'subway station', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_TTC_station', 104349077, 0, 'subway station', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_Montreal_Metro', 104349077, 0, 'subway station', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_T&W_Metro_station', 104349077, 0, 'subway station', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_Stadium', 104295881, 0, 'stadium, bowl, arena, sports stadium', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_Baseball_Stadium', 104295881, 0, 'stadium, bowl, arena, sports stadium', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_Skyscraper', 104233124, 0, 'skyscraper', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_Ship', 104194289, 0, 'ship', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_roller_coaster', 104102406, 0, 'roller coaster, big dipper, chute-the-chute', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_road', 104096066, 0, 'road,route', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_U.S._Route', 104096066, 0, 'road, route', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_Australian_Road', 104096066, 0, 'road, route', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_NY_County_Route', 104096066, 0, 'road, route', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_MA_Route', 104096066, 0, 'road, route', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_UK_station', 104049098, 0, 'railwaystation,railroadstation,railroadterminal,trainstation,traindepot', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_Station', 104049098, 0, 'railwaystation,railroadstation,railroadterminal,trainstation,traindepot', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_NYCS', 104049098, 0, 'railwaystation,railroadstation,railroadterminal,trainstation,traindepot', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_London_station', 104049098, 0, 'railwaystation,railroadstation,railroadterminal,trainstation,traindepot', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_PKP_station', 104049098, 0, 'railwaystation,railroadstation,railroadterminal,trainstation,traindepot', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_SG_rail', 104048568, 0, 'railway, railroad, railroad line, railway line, railway system', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('infobox_rail', 104048568, 0, 'railway, railroad, railroad line, railway line, railway system', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_Rail_companies', 104048568, 0, 'railway, railroad, railroad line, railway line, railway system', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_Radio_Station', 104044119, 0, 'radiostation', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_Prisons', 104005630, 0, 'prison, prison house', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('infobox_shopping_mall', 103965456, 0, 'plaza, mall, center, shopping mall, shopping center, shopping centre', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_Single', 103924069, 107048000, 'phonograph record, phonograph recording, record, disk, disc, platter', 'song', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Single_infobox', 103924069, 107048000, 'phonograph record, phonograph recording, record, disk, disc, platter', 'song', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_music_DVD', 103924069, 0, 'phonograph record, phonograph recording, record, disk, disc, platter', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Single_infobox2', 103924069, 107048000, 'phonograph record, phonograph recording, record, disk, disc, platter', 'song', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_Network', 103820474, 0, 'network\n', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('infobox_Museum', 103800563, 0, 'museum\n', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_Missile', 103773504, 0, 'missile\n', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_zoo', 103745146, 0, 'menagerie, zoo, zoological garden', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_Locomotive', 103684823, 0, 'locomotive, engine, locomotive engine, railway locomotive', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_TX_State_Highway', 103519981, 0, 'highway, main road', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_MN_state_highway', 103519981, 0, 'highway, main road', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_Interstate', 103519981, 0, 'highway, main road', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_NH_Route', 103519981, 0, 'highway, main road', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_Oklahoma_Highway', 103519981, 0, 'highway, main road', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_Maryland_highway', 103519981, 0, 'highway, main road', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_NC_Route', 103519981, 0, 'highway, main road', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('infobox_LA_Highway', 103519981, 0, 'highway, main road', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_Kentucky_Highway', 103519981, 0, 'highway, main road', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_computer', 103082979, 0, 'computer, computing machine, computing device, data processor, electronic computer, information processing system', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_Broadcast', 103006398, 0, 'channel, television channel, TV channel', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_TV_channel', 103006398, 0, 'channel, television channel, TV channel', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_Automobile', 102958343, 0, 'car,auto,automobile,machine,motorcar', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_Automobile_generation', 102958343, 0, 'car, auto, automobile, machine, motorcar', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_Historic_building', 102913152, 0, 'building, edifice', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_Bridge', 102898711, 0, 'bridge, span', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_Brewery', 102897237, 0, 'brewery', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('infobox_Book', 102870092, 0, 'book,volume', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_Lighthouse', 102814860, 0, 'beacon, lighthouse, beacon light, pharos', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Airport_infobox', 102692232, 0, 'airport, airdrome, aerodrome', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_Airline', 102690081, 0, 'airline,airlinebusiness,airway', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('infobox_Aircraft', 102686568, 0, 'aircraft', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_terrorist_attack', 101246697, 0, 'terrorist attack', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_Military_Conflict', 100973077, 0, 'war,warfare', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('infobox_music_festival', 100517728, 0, 'festival, fete', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_Pinball', 100459284, 0, 'pinball, pinball game', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_CVG', 100458890, 0, 'computergame,videogame', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('infobox_RPG', 100455599, 0, 'game', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_Game', 100455599, 0, 'game', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_UK_place', 100027167, 0, 'location', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_Australian_Place', 100027167, 0, 'location', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_nrhp', 100027167, 0, 'location', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_Irish_Place', 100027167, 0, 'location', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('infobox_Scotland_place_with_map', 100027167, 103720163, 'location', 'map', NULL);
INSERT INTO `wordnet_mapping` VALUES ('Infobox_Military_Person', 100007846, 0, 'person, individual, someone, somebody, mortal, human, soul', '', NULL);
INSERT INTO `wordnet_mapping` VALUES ('infobox_person', 100007846, 0, 'person, individual, someone, somebody, mortal, human, soul', '', NULL);

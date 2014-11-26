DROP TABLE images;

CREATE TABLE images (
   photo_id    int,
   owner_name  varchar(24),
   permitted   int,
   subject     varchar(128),
   place       varchar(128),
   timing      date,
   description varchar(2048),
   thumbnail   blob,
   photo       blob,
   PRIMARY KEY(photo_id),
   FOREIGN KEY(owner_name) REFERENCES users,
   FOREIGN KEY(permitted) REFERENCES groups
) tablespace c391ware;

CREATE INDEX descIndex ON images(description) INDEXTYPE IS CTXSYS.CONTEXT; 
CREATE INDEX subjIndex ON images(subject) INDEXTYPE IS CTXSYS.CONTEXT; 
CREATE INDEX placeIndex ON images(place) INDEXTYPE IS CTXSYS.CONTEXT;

drop table popular_images;

CREATE TABLE popular_images (
   user_name varchar(24),
   photo_id int,
   PRIMARY KEY(user_name, photo_id),
   FOREIGN KEY(user_name) REFERENCES users,
   FOREIGN KEY(photo_id) REFERENCES images
);

CREATE OR REPLACE VIEW total_views AS
SELECT * FROM(
SELECT photo_id, count(*) total
FROM popular_images
GROUP BY photo_id
UNION
SELECT photo_id, 0
FROM images
WHERE photo_id not in
(SELECT DISTINCT photo_id FROM popular_images)
) ORDER BY total desc;

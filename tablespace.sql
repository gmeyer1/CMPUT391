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

drop table popular_images;

CREATE TABLE popular_images (
   user_name varchar(24),
   photo_id int,
   PRIMARY KEY(user_name, photo_id),
   FOREIGN KEY(user_name) REFERENCES users,
   FOREIGN KEY(photo_id) REFERENCES images
);

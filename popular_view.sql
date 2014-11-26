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

SELECT i.photo_id, i.thumbnail FROM images i
JOIN total_views v ON v.photo_id = i.photo_id
WHERE v.total IN (
    SELECT t.total FROM total_views t
    join images p on p.photo_id = t.photo_id 
    WHERE ROWNUM < 6
    and (p.owner_name = 'gmeyer1' or 'gmeyer1'='admin' or p.permitted = 1 or
    p.permitted in (SELECT group_id FROM group_lists WHERE friend_id = 'gmeyer1'))
)
and (i.owner_name = 'gmeyer1' or 'gmeyer1'='admin' or i.permitted = 1 or
i.permitted in (SELECT group_id FROM group_lists WHERE friend_id = 'gmeyer1'))
ORDER BY v.total desc;

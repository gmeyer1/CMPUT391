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
WHERE v.total IN (SELECT total FROM total_views WHERE ROWNUM < 6)
ORDER BY v.total desc;

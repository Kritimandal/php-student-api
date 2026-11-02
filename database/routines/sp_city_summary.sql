CREATE PROCEDURE sp_city_summary()
BEGIN
    SELECT
        city,
        COUNT(*) AS students,
        ROUND(AVG(age), 1) AS average_age,
        MIN(age) AS youngest,
        MAX(age) AS oldest
    FROM students
    GROUP BY city
    ORDER BY students DESC, city;
END

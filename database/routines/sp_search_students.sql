CREATE PROCEDURE sp_search_students(
    IN p_term VARCHAR(100),
    IN p_city VARCHAR(100),
    IN p_limit INT,
    IN p_offset INT
)
BEGIN
    SELECT id, name, age, city, created_at, updated_at
    FROM students
    WHERE (p_term IS NULL OR name LIKE CONCAT('%', p_term, '%') COLLATE utf8mb4_unicode_ci ESCAPE '!' OR city LIKE CONCAT('%', p_term, '%') COLLATE utf8mb4_unicode_ci ESCAPE '!')
      AND (p_city IS NULL OR city = p_city COLLATE utf8mb4_unicode_ci)
    ORDER BY id
    LIMIT p_limit OFFSET p_offset;
END

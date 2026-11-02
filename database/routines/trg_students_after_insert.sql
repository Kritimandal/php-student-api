CREATE TRIGGER trg_students_after_insert
AFTER INSERT ON students
FOR EACH ROW
INSERT INTO student_audit (student_id, action, old_data, new_data)
VALUES (
    NEW.id,
    'insert',
    NULL,
    JSON_OBJECT('name', NEW.name, 'age', NEW.age, 'city', NEW.city)
)

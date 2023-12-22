DROP PROCEDURE IF EXISTS addFieldIfNotExists;

DROP FUNCTION IF EXISTS isFieldExisting;

CREATE FUNCTION isFieldExisting (table_name_IN VARCHAR(100), field_name_IN VARCHAR(100))
RETURNS INT
RETURN (
    SELECT COUNT(COLUMN_NAME)
    FROM INFORMATION_SCHEMA.columns
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = table_name_IN
    AND COLUMN_NAME = field_name_IN
);

CREATE PROCEDURE addFieldIfNotExists (
    IN table_name_IN VARCHAR(100)
    , IN field_name_IN VARCHAR(100)
    , IN field_definition_IN VARCHAR(100)
)
BEGIN

    SET @isFieldThere = isFieldExisting(table_name_IN, field_name_IN);
    IF (@isFieldThere = 0) THEN

        SET @ddl = CONCAT('ALTER TABLE ', table_name_IN);
        SET @ddl = CONCAT(@ddl, ' ', 'ADD COLUMN') ;
        SET @ddl = CONCAT(@ddl, ' ', field_name_IN);
        SET @ddl = CONCAT(@ddl, ' ', field_definition_IN);

        PREPARE stmt FROM @ddl;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;

    END IF;

END;

SET FOREIGN_KEY_CHECKS=0;

CREATE TABLE IF NOT EXISTS $plansTable (
    plan_id bigint(50) UNSIGNED NOT NULL AUTO_INCREMENT,
    room_id bigint(50) UNSIGNED NOT NULL,
    start_time datetime NULL,
    end_time datetime NULL,
    price int(20) UNSIGNED,
    priority_order bigint(50) UNSIGNED,
    plan_type text,
    insert_date timestamp DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (plan_id),
    FOREIGN KEY (room_id) REFERENCES $postsTable(ID)
) $charset_collate;



CREATE TABLE IF NOT EXISTS $bookingDetailsTable (
    item_id bigint(50) UNSIGNED NOT NULL AUTO_INCREMENT,
    book_id bigint(50) UNSIGNED NOT NULL,
    room_id bigint(50) UNSIGNED NOT NULL,
    checkin_date datetime NOT NULL,
    checkout_date datetime NOT NULL,
    quantity int(20) UNSIGNED NOT NULL,
    number_of_people int(20) UNSIGNED NOT NULL,
    total_price float(20) UNSIGNED NOT NULL,
    insert_date timestamp DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (item_id),
    FOREIGN KEY (book_id) REFERENCES $postsTable(ID),
    FOREIGN KEY (room_id) REFERENCES $postsTable(ID)
) $charset_collate;

CALL addFieldIfNotExists ('$bookingDetailsTable', 'plan_id', 'bigint(50) UNSIGNED NOT NULL');
ALTER TABLE $bookingDetailsTable ADD FOREIGN KEY (plan_id) REFERENCES $plansTable(plan_id);



CREATE TABLE IF NOT EXISTS $closedRoomsTable (
    id bigint(50) UNSIGNED NOT NULL AUTO_INCREMENT,
    room_id bigint(50) UNSIGNED NOT NULL,
    start_date datetime NOT NULL,
    end_date datetime NOT NULL,
    PRIMARY KEY (id),
    FOREIGN KEY (room_id) REFERENCES $postsTable(ID)
) $charset_collate;



CREATE TABLE IF NOT EXISTS $activityLogTable (
    id bigint(50) UNSIGNED NOT NULL AUTO_INCREMENT,
    date datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    activity_type text NOT NULL,
    details text NOT NULL,
    PRIMARY KEY (id)
) $charset_collate;



CREATE TABLE IF NOT EXISTS $pricingPlansMetaTable (
    id bigint(50) UNSIGNED NOT NULL AUTO_INCREMENT,
    plan_id bigint(50) UNSIGNED NOT NULL,
    meta_key varchar(255) NOT NULL,
    meta_value text NOT NULL,
    PRIMARY KEY (id),
    FOREIGN KEY (plan_id) REFERENCES $postsTable(ID)
) $charset_collate;

SET FOREIGN_KEY_CHECKS=1;
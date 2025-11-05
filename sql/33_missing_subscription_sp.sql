 /* PREPAREMODE */

CREATE
    PROCEDURE `nevererweb`.`CreateMissingSubscriptions`()
    LANGUAGE SQL
    MODIFIES SQL DATA
    SQL SECURITY DEFINER
    COMMENT 'Creates subscriptions for subscribed_by_default tomes where users do not already have one and have not already unsubscribed'
BEGIN
        INSERT INTO subscriptions (user_id, tome_id, subscribed, created, modified)
        SELECT u.id AS user_id, t.id AS tome_id, 1, NOW(), NOW()
        FROM users u
        CROSS JOIN tomes t
        LEFT JOIN subscriptions s ON s.user_id = u.id AND s.tome_id = t.id
        WHERE t.subscribed_by_default = 1
        AND s.subscribed IS NULL
        ;

END;
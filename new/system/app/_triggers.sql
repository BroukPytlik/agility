
-- trigger for removing permissions on user delete
DROP TRIGGER  IF EXISTS `trOnUserDelete`;

DELIMITER ;;

CREATE TRIGGER `trOnUserDelete` BEFORE DELETE ON `user` FOR EACH ROW
BEGIN
 DELETE FROM permission WHERE userId = OLD.id;
END;;

DELIMITER ;


DROP TRIGGER  IF EXISTS `item_delete_calendar`;

DELIMITER ;;

CREATE TRIGGER `item_delete_calendar` BEFORE DELETE ON `item` FOR EACH ROW
DELETE FROM calendar WHERE OLD.id = calendar.item_id;;

DELIMITER ;
-- รันในฐานข้อมูลของ Hosting ที่เลือกไว้แล้ว (ห้ามรัน CREATE DATABASE)
SET NAMES utf8mb4;

-- แก้ภาษาไทยเพี้ยนจากตารางเดิม
ALTER TABLE `17_users` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `17_tasks` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `17_comments` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `17_reactions` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `17_notifications` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- แก้ปัญหาเพิ่มงานแล้วไม่มี id ทำให้แก้ไข/ลบไม่ได้
ALTER TABLE `17_tasks`
    MODIFY `id` INT UNSIGNED NOT NULL AUTO_INCREMENT;

-- ตรวจให้ตารางที่เกี่ยวข้องใช้ InnoDB เพื่อให้ foreign key และการลบ cascade ทำงาน
ALTER TABLE `17_users` ENGINE=InnoDB;
ALTER TABLE `17_tasks` ENGINE=InnoDB;
ALTER TABLE `17_comments` ENGINE=InnoDB;
ALTER TABLE `17_reactions` ENGINE=InnoDB;
ALTER TABLE `17_notifications` ENGINE=InnoDB;

-- ถ้าตารางหรือคอลัมน์ยังไม่มี ให้ Import schema.sql ก่อน แล้วจึงรันไฟล์นี้

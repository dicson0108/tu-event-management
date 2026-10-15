-- Seed Data for Taylor's University Event Management
USE `tu_event_management`;

-- Password is "Password123!" for all seeded users
SET @pw_hash = '$2y$10$Uy7IHz175ZZhgO6FnpD0M.YJJtS2769/wqa5g3bY8fJxzeC1/V3cm';

-- 1. Insert Users
INSERT INTO `users` (`id`, `name`, `preferred_name`, `email`, `password_hash`, `role`, `status`, `avatar`, `phone`, `faculty`, `created_at`) VALUES
(1, 'Nadia Rahman', 'Nadia', 'nadia.rahman@taylors.edu.my', @pw_hash, 'admin', 'active', NULL, '+60 12 987 6543', 'Campus Life Administration', '2026-08-01 10:00:00'),
(2, 'Aisha Tan', 'Aisha', 'aisha.tan@sd.taylors.edu.my', @pw_hash, 'user', 'active', NULL, '+60 12 345 6789', 'The Design School', '2026-09-01 09:30:00'),
(3, 'Daniel Lee', 'Daniel', 'daniel.lee@sd.taylors.edu.my', @pw_hash, 'user', 'active', NULL, '+60 17 888 1234', 'School of Computer Science', '2026-09-05 11:15:00'),
(4, 'Sara Ahmad', 'Sara', 'sara.ahmad@sd.taylors.edu.my', @pw_hash, 'user', 'active', NULL, '+60 19 456 7890', 'School of Media & Communication', '2026-09-10 14:20:00'),
(5, 'Marcus Wong', 'Marcus', 'marcus.wong@sd.taylors.edu.my', @pw_hash, 'user', 'active', NULL, '+60 16 333 4455', 'Taylor\'s Business School', '2026-09-12 16:00:00'),
(6, 'Chloe Lim', 'Chloe', 'chloe.lim@sd.taylors.edu.my', @pw_hash, 'user', 'active', NULL, '+60 11 222 3344', 'School of Bioscience', '2026-09-15 10:45:00')
ON DUPLICATE KEY UPDATE `email` = VALUES(`email`);

-- 2. Insert Categories
INSERT INTO `categories` (`id`, `name`, `slug`, `icon`) VALUES
(1, 'Arts & culture', 'arts-culture', 'palette'),
(2, 'Music', 'music', 'music'),
(3, 'Tech & innovation', 'tech-innovation', 'code'),
(4, 'Careers', 'careers', 'briefcase'),
(5, 'Community', 'community', 'heart'),
(6, 'Wellbeing', 'wellbeing', 'activity')
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

-- 3. Insert Events
INSERT INTO `events` (`id`, `title`, `description`, `category_id`, `event_date`, `start_time`, `end_time`, `format`, `venue`, `online_link`, `capacity`, `poster_theme`, `status`, `allow_waitlist`, `organiser_name`, `organiser_email`, `created_by`, `created_at`) VALUES
(1, 'Design Futures Festival', 'A day of bold ideas, hands-on making and conversations about the world we\'re creating next. Join Taylor\'s Design School for a campus-wide celebration of creative thinking. Meet emerging designers, explore student installations and try your hand at collaborative design challenges. No design experience needed — just bring your curiosity.', 1, '2026-10-24', '10:00:00', '17:00:00', 'physical', 'The Grand Hall, Taylor\'s Lakeside Campus', NULL, 200, 'purple', 'published', 1, 'Taylor\'s Design School', 'designschool@taylors.edu.my', 1, '2026-10-01 10:00:00'),
(2, 'Lakeside After Hours', 'Good music. Great company. Late-night acoustic sets, live student bands, and food trucks along the lakeside promenade. Unwind with friends under the stars.', 2, '2026-10-30', '18:00:00', '22:00:00', 'physical', 'Lakeside Amphitheatre', NULL, 300, 'dark', 'published', 1, 'Taylor\'s Music Society', 'musicsociety@taylors.edu.my', 1, '2026-10-02 11:30:00'),
(3, 'Build / Break Hackathon', '12 hours. One bold idea. Build functional software and hardware prototypes tackling campus sustainability, digital productivity, and community welfare. Mentors and meals provided.', 3, '2026-11-07', '09:00:00', '21:00:00', 'hybrid', 'Block E + Microsoft Teams', 'https://teams.microsoft.com/l/meetup-join/build-break-2026', 120, 'yellow', 'published', 1, 'Taylor\'s Tech Society', 'techsociety@taylors.edu.my', 1, '2026-10-05 14:00:00'),
(4, 'Your Next Chapter', 'Meet the alumni and industry leaders shaping what\'s next. Gain actionable career guidance, portfolio reviews, and explore graduate opportunities across leading firms in Southeast Asia.', 4, '2026-11-12', '14:00:00', '17:00:00', 'online', 'Microsoft Teams', 'https://teams.microsoft.com/l/meetup-join/your-next-chapter', 150, 'blue', 'published', 1, 'Taylor\'s Career Centre', 'careers@taylors.edu.my', 1, '2026-10-08 09:15:00'),
(5, 'The Green Campus Project', 'Small acts. A greener campus. Join our community garden workshop, learn urban composting, and help construct automated solar-powered irrigation for the lakeside herb garden.', 5, '2026-11-14', '08:30:00', '12:00:00', 'physical', 'Urban Farm, Lakeside', NULL, 40, 'green', 'published', 1, 'Taylor\'s Green Initiative', 'green@taylors.edu.my', 1, '2026-10-10 16:45:00'),
(6, 'Lakeside Sunrise Run', 'Find your pace. Find your people. A brisk 5KM scenic run around the lake followed by guided breathing sessions, healthy fruit smoothies, and community hangout.', 6, '2026-11-21', '06:30:00', '09:00:00', 'physical', 'Lakeside Boardwalk', NULL, 100, 'peach', 'published', 1, 'Taylor\'s Wellness Club', 'wellness@taylors.edu.my', 1, '2026-10-12 08:00:00'),
(7, 'Creative Confidence Workshop', 'An afternoon of creative exercises, collaborative challenges and finding the confidence to try something new. Step out of your comfort zone in a supportive studio setting.', 1, '2026-10-03', '14:00:00', '16:00:00', 'physical', 'Design Studio, Block D', NULL, 40, 'blue', 'completed', 1, 'Taylor\'s Design School', 'designschool@taylors.edu.my', 1, '2026-09-20 10:00:00'),
(8, 'Future Skills: AI Studio', 'Hands-on practical exploration of modern generative models, safe prompting patterns, and building AI-enhanced applications responsibly.', 3, '2026-11-28', '10:00:00', '15:00:00', 'hybrid', 'Block C + Microsoft Teams', 'https://teams.microsoft.com/l/meetup-join/ai-studio', 80, 'dark', 'draft', 1, 'School of Computer Science', 'cs@taylors.edu.my', 1, '2026-10-14 17:00:00')
ON DUPLICATE KEY UPDATE `title` = VALUES(`title`);

-- 4. Insert Registrations matching mockups
-- Event 1: Aisha, Daniel, Sara confirmed
INSERT IGNORE INTO `registrations` (`event_id`, `user_id`, `status`, `waitlist_position`) VALUES
(1, 2, 'confirmed', NULL),
(1, 3, 'confirmed', NULL),
(1, 4, 'confirmed', NULL),
(1, 5, 'confirmed', NULL);

-- Event 2 (Full): Lakeside After Hours
INSERT IGNORE INTO `registrations` (`event_id`, `user_id`, `status`, `waitlist_position`) VALUES
(2, 3, 'confirmed', NULL),
(2, 4, 'confirmed', NULL),
(2, 5, 'confirmed', NULL),
(2, 6, 'confirmed', NULL);

-- Event 3: Build / Break Hackathon (Aisha confirmed)
INSERT IGNORE INTO `registrations` (`event_id`, `user_id`, `status`, `waitlist_position`) VALUES
(3, 2, 'confirmed', NULL),
(3, 3, 'confirmed', NULL);

-- Event 4: Your Next Chapter
INSERT IGNORE INTO `registrations` (`event_id`, `user_id`, `status`, `waitlist_position`) VALUES
(4, 3, 'confirmed', NULL),
(4, 4, 'confirmed', NULL);

-- Event 5 (Full + Waitlist): Aisha is waitlisted #3, others waitlisted #1, #2
INSERT IGNORE INTO `registrations` (`event_id`, `user_id`, `status`, `waitlist_position`) VALUES
(5, 4, 'waitlisted', 1),
(5, 5, 'waitlisted', 2),
(5, 2, 'waitlisted', 3),
(5, 6, 'waitlisted', 4);

-- Event 7: Creative Confidence Workshop (Completed, Aisha attended)
INSERT IGNORE INTO `registrations` (`event_id`, `user_id`, `status`, `waitlist_position`) VALUES
(7, 2, 'confirmed', NULL),
(7, 3, 'confirmed', NULL),
(7, 4, 'confirmed', NULL);

-- 5. Insert Comments & Ratings for Event 7
INSERT IGNORE INTO `comments` (`event_id`, `user_id`, `rating`, `comment`, `created_at`) VALUES
(7, 2, 5, 'The hands-on exercises helped me get out of my comfort zone. Loved meeting people from other schools!', '2026-10-04 14:10:00'),
(7, 3, 5, 'The group exercises made it so easy to meet new people. Great energy from the facilitators!', '2026-10-04 15:30:00'),
(7, 4, 5, 'Loved the hands-on approach. More sessions like this please!', '2026-10-04 16:45:00');

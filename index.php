<?php

/* Database schema

CREATE TABLE `castellanario` (
  `id` bigint UNSIGNED NOT NULL,
  `term` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `term_slug` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `region` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `region_slug` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `upvotes` bigint UNSIGNED NOT NULL,
  `downvotes` bigint UNSIGNED NOT NULL,
  `flags` int UNSIGNED NOT NULL,
  `explanation` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `example` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `posted` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE `castellanario`
  ADD PRIMARY KEY (`id`),
  ADD KEY `term` (`term`),
  ADD KEY `term_slug` (`term_slug`),
  ADD KEY `region` (`region`),
  ADD KEY `region_slug` (`region_slug`),
  ADD KEY `upvotes` (`upvotes`),
  ADD KEY `downvotes` (`downvotes`),
  ADD KEY `posted` (`posted`);

ALTER TABLE `castellanario`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;
COMMIT;

*/

/* Connect to database */

/* Perform POST actions
- Should post a new term? (validate and do or error)
- Should vote? (validate and do or error)
*/

/* Print HTML header with very simple CSS styles */

/* Should show any form?
- Show the "add term" form (with errors if any) and Captcha
 */

/* IF NO FORM: Fetch data and show
- Any filter? (search, region, random, order_by)
*/

/* Print HTML footer */

SELECT f.*
FROM (
	# sql request
	SELECT e.id, e.ls_score, e.l_score, e.g_score, e.ls_previous, e.l_previous, e.g_previous, e.created, e.user_id, e.premium, e.hotspot,
		MAX(gu.group_id) AS group_id
	FROM (
		# Select all applications with other user scores from global excluding canceled applications (in time) and locked sessions
		SELECT
			d.id,
			d.session_id,
			d.session_date,
			d.user_id,
			d.ls_count,
			d.ls_score,
			d.ls_tr_ratio,
			d.ls_temp,
			d.ls_pn_ratio,
			d.ls_previous,
			d.l_count,
			d.l_score,
			d.l_tr_ratio,
			d.l_temp,
			d.l_pn_ratio,
			d.l_previous,
			d.g_count,
			d.g_score,
			d.g_tr_ratio,
			d.g_temp,
			d.g_pn_ratio,
			d.g_previous,
			# Compute count, score, tr_ratio, temp and pn_ratio for global
			COUNT(a5.id) AS o_count,
			SUM(IF(a5.id IS NOT NULL, 1/ABS(DATEDIFF(d.session_date, ADDDATE(s5.date, INTERVAL IF(s5.slot_id = 4, 1, 0) DAY))), 0)) AS o_score,
			AVG(IF(a5.id IS NOT NULL AND s5.temperature IS NOT NULL AND s5.rainfall IS NOT NULL, s5.temperature/(1+s5.rainfall), NULL)) AS o_tr_ratio,
			AVG(IF(a5.id IS NOT NULL AND s5.temperature IS NOT NULL, s5.temperature, NULL)) AS o_temp,
			(SUM(IF(a5.id IS NOT NULL AND s5.premium = 1, 1, 0))+1)/(SUM(IF(a5.id IS NOT NULL AND s5.premium = 0, 1, 0))+1) AS o_pn_ratio,
			MAX(IF(a5.id IS NOT NULL, s5.date, NULL)) AS o_previous,
			d.remaining,
			d.premium,
			d.hotspot,
			d.created
		FROM (
			# Select all applications with scores from global excluding canceled applications (in time) and locked sessions
			SELECT
				c.id,
				c.session_id,
				c.session_date,
				c.user_id,
				c.ls_count,
				c.ls_score,
				c.ls_tr_ratio,
				c.ls_temp,
				c.ls_pn_ratio,
				c.ls_previous,
				c.l_count,
				c.l_score,
				c.l_tr_ratio,
				c.l_temp,
				c.l_pn_ratio,
				c.l_previous,
				# Compute count, score, tr_ratio, temp and pn_ratio for global
				COUNT(a4.id) AS g_count,
				SUM(IF(a4.id IS NOT NULL, 1/ABS(DATEDIFF(c.session_date, ADDDATE(s4.date, INTERVAL IF(s4.slot_id = 4, 1, 0) DAY))), 0)) AS g_score,
				AVG(IF(a4.id IS NOT NULL AND s4.temperature IS NOT NULL AND s4.rainfall IS NOT NULL, s4.temperature/(1+s4.rainfall), NULL)) AS g_tr_ratio,
				AVG(IF(a4.id IS NOT NULL AND s4.temperature IS NOT NULL, s4.temperature, NULL)) AS g_temp,
				(SUM(IF(a4.id IS NOT NULL AND s4.premium = 1, 1, 0))+1)/(SUM(IF(a4.id IS NOT NULL AND s4.premium = 0, 1, 0))+1) AS g_pn_ratio,
				#MIN(IF(a4.id IS NOT NULL, DATEDIFF(c.session_date, ADDDATE(s4.date, INTERVAL IF(s4.slot_id = 4, 1, 0) DAY)), NULL)) AS g_previous,
				MIN(IF(a4.id IS NOT NULL, DATEDIFF(c.session_date, s4.date), NULL)) AS g_previous,
				c.remaining,
				c.premium,
				c.hotspot,
				c.created
			FROM (
				# Select all applications with scores from same location excluding canceled applications (in time) and locked sessions
				SELECT
					b.id,
					b.session_id,
					b.session_date,
					/*remonter location_id ici, on veut prendre la température pour la même location + slot_id IN (2, 3)*/
					b.user_id,
					b.ls_count,
					b.ls_score,
					b.ls_tr_ratio,
					b.ls_temp,
					b.ls_pn_ratio,
					b.ls_previous,
					# Compute count, score, tr_ratio, temp and pn_ratio for same location
					COUNT(a3.id) AS l_count,
					SUM(IF(a3.id IS NOT NULL, 1/ABS(DATEDIFF(b.session_date, ADDDATE(s3.date, INTERVAL IF(s3.slot_id = 4, 1, 0) DAY))), 0)) AS l_score,
					AVG(IF(a3.id IS NOT NULL AND s3.temperature IS NOT NULL AND s3.rainfall IS NOT NULL, s3.temperature/(1+s3.rainfall), NULL)) AS l_tr_ratio,
					AVG(IF(a3.id IS NOT NULL AND s3.temperature IS NOT NULL, s3.temperature, NULL)) AS l_temp,
					(SUM(IF(a3.id IS NOT NULL AND s3.premium = 1, 1, 0))+1)/(SUM(IF(a3.id IS NOT NULL AND s3.premium = 0, 1, 0))+1) AS l_pn_ratio,
					#MIN(IF(a3.id IS NOT NULL, DATEDIFF(b.session_date, ADDDATE(s3.date, INTERVAL IF(s3.slot_id = 4, 1, 0) DAY)), NULL)) AS l_previous,
					MIN(IF(a3.id IS NOT NULL, DATEDIFF(b.session_date, s3.date), NULL)) AS l_previous,
					/*#TODO: calculer les délais guest|regular ici ou plus haut et les remonter (au denier cran c'est mieux pour limiter la taille de la temp table)*/
					b.remaining,
					b.premium,
					b.hotspot,
					b.created
				FROM (
					# Select all applications with scores from same location+slot excluding canceled applications (in time) and locked sessions
					SELECT
						a.id,
						s.id AS session_id,
						ADDDATE(s.date, INTERVAL IF(s2.slot_id = 4, 1, 0) DAY) AS session_date,
						s.location_id,
						a.user_id,
						# Compute count, score, tr_ratio, temp and pn_ratio for same location and slot
						COUNT(a2.id) AS ls_count,
						SUM(IF(a2.id IS NOT NULL, 1/ABS(DATEDIFF(s.date, ADDDATE(s2.date, INTERVAL IF(s2.slot_id = 4, 1, 0) DAY))), 0)) AS ls_score,
						AVG(IF(a2.id IS NOT NULL AND s2.temperature IS NOT NULL AND s2.rainfall IS NOT NULL, s2.temperature/(1+s2.rainfall), NULL)) AS ls_tr_ratio,
						AVG(IF(a2.id IS NOT NULL AND s2.temperature IS NOT NULL, s2.temperature, NULL)) AS ls_temp,
						(SUM(IF(a2.id IS NOT NULL AND s2.premium = 1, 1, 0))+1)/(SUM(IF(a2.id IS NOT NULL AND s2.premium = 0, 1, 0))+1) AS ls_pn_ratio,
						#MIN(IF(a2.id IS NOT NULL, DATEDIFF(s.date, ADDDATE(s2.date, INTERVAL IF(s2.slot_id = 4, 1, 0) DAY)), NULL)) AS ls_previous,
						MIN(IF(a2.id IS NOT NULL, DATEDIFF(s.date, s2.date), NULL)) AS ls_previous,
						/*#TODO: calculer les délais guest|regular ici ou plus haut et les remonter (au denier cran c'est mieux pour limiter la taille de la temp table)*/
						TIMEDIFF(ADDDATE(ADDTIME(s.date, s.begin), INTERVAL IF(s2.slot_id = 4, 1, 0) DAY), NOW()) AS remaining,
						s.premium,
						l.hotspot,
						a.created
					FROM sessions AS s
					JOIN locations AS l ON (l.id = s.location_id)
					JOIN applications AS a ON (a.session_id = s.id AND a.canceled IS NULL)
					LEFT JOIN sessions AS s2 ON (s2.id != s.id AND s2.location_id = s.location_id AND s2.slot_id = s.slot_id AND s2.application_id IS NOT NULL AND s2.locked IS NULL)
					LEFT JOIN applications AS a2 ON (a2.id = s2.application_id AND a2.user_id = a.user_id AND (a2.canceled IS NULL OR TIMESTAMPDIFF(DAY, a2.canceled, ADDDATE(ADDTIME(s2.date, s2.begin), INTERVAL IF(s2.slot_id = 4, 1, 0) DAY)) < 1))
					WHERE s.id = 12
					GROUP BY a.id
					ORDER BY NULL
					LIMIT 0, 1000000
				) AS b
				LEFT JOIN sessions AS s3 ON (s3.id != b.session_id AND s3.location_id = b.location_id AND s3.application_id IS NOT NULL AND s3.locked IS NULL)
				LEFT JOIN applications AS a3 ON (a3.id = s3.application_id AND a3.user_id = b.user_id AND (a3.canceled IS NULL OR TIMESTAMPDIFF(DAY, a3.canceled, ADDDATE(ADDTIME(s3.date, s3.begin), INTERVAL IF(s3.slot_id = 4, 1, 0) DAY)) < 1))
				GROUP BY b.id
				ORDER BY NULL
				LIMIT 0, 1000000
			) AS c
			LEFT JOIN sessions AS s4 ON (s4.id != c.session_id AND s4.application_id IS NOT NULL AND s4.locked IS NULL)
			LEFT JOIN applications AS a4 ON (a4.id = s4.application_id AND a4.user_id = c.user_id AND (a4.canceled IS NULL OR TIMESTAMPDIFF(DAY, a4.canceled, ADDDATE(ADDTIME(s4.date, s4.begin), INTERVAL IF(s4.slot_id = 4, 1, 0) DAY)) < 1))
			GROUP BY c.id
			ORDER BY NULL
			LIMIT 0, 1000000
		) AS d
		LEFT JOIN sessions AS s5 ON (s5.id != d.session_id AND s5.application_id IS NOT NULL AND s5.locked IS NULL)
		LEFT JOIN applications AS a5 ON (a5.id = s5.application_id AND a5.user_id != d.user_id AND (a5.canceled IS NULL OR TIMESTAMPDIFF(DAY, a5.canceled, ADDDATE(ADDTIME(s5.date, s5.begin), INTERVAL IF(s5.slot_id = 4, 1, 0) DAY)) < 1))
		GROUP BY d.id
		ORDER BY NULL
		LIMIT 0, 1000000
	) AS e
	LEFT JOIN groups_users AS gu ON (gu.user_id = e.user_id)
	GROUP BY e.id
) AS f
/*TODO: vérifier comment se comporte o_tr_ratio quand on a zéro réservation ^_^ à l'initialisation au hasard */
WHERE
	IF(f.group_id <= 2 AND f.l_previous <= 30, f.remaining <= SEC_TO_TIME(2*24*3600), 1) AND
	IF(f.group_id <= 3 AND f.premium = 1 AND f.hotspot = 1, f.remaining <= SEC_TO_TIME(3*24*3600), 1) AND
	IF(f.group_id <= 4 AND f.l_count <= 5, f.remaining <= SEC_TO_TIME(3*24*3600), 1) AND
	IF(f.group_id <= 4 AND f.l_pn_ratio >= 1, f.remaining <= SEC_TO_TIME(3*24*3600), 1) AND
	IF(f.group_id <= 4 AND f.l_tr_ratio >= (f.o_tr_ratio + 5), f.remaining <= SEC_TO_TIME(3*24*3600), 1)
ORDER BY f.l_score ASC, f.g_score ASC, f.created ASC, f.user_id ASC

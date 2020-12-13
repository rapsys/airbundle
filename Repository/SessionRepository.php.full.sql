SELECT
	e.id,
	e.user_id,
	e.group_id,
	e.hotspot,
	e.premium,
	e.created,
	e.l_count,
	e.l_score,
	e.g_score,
	e.l_pn_ratio,
	e.l_tr_ratio,
	e.o_tr_ratio,
	e.l_previous,
	e.remaining
FROM (
	SELECT
		d.id,
		d.session_id,
		d.session_date,
		d.location_id,
		d.user_id,
		d.l_count,
		d.l_score,
		d.l_tr_ratio,
		d.l_pn_ratio,
		d.l_previous,
		d.g_count,
		d.g_score,
		d.g_tr_ratio,
		d.g_pn_ratio,
		d.g_previous,
		d.o_count,
		d.o_score,
		d.o_tr_ratio,
		d.o_pn_ratio,
		d.o_previous,
		MAX(gu.group_id) AS group_id,
		d.remaining,
		d.premium,
		d.hotspot,
		d.created
	FROM (
		SELECT
			c.id,
			c.session_id,
			c.session_date,
			c.location_id,
			c.user_id,
			c.l_count,
			c.l_score,
			c.l_tr_ratio,
			c.l_pn_ratio,
			c.l_previous,
			c.g_count,
			c.g_score,
			c.g_tr_ratio,
			c.g_pn_ratio,
			c.g_previous,
			COUNT(a4.id) AS o_count,
			SUM(IF(a4.id IS NOT NULL, 1/ABS(DATEDIFF(c.session_date, ADDDATE(s4.date, INTERVAL IF(s4.slot_id = 4, 1, 0) DAY))), 0)) AS o_score,
			AVG(IF(a4.id IS NOT NULL AND s4.temperature IS NOT NULL AND s4.rainfall IS NOT NULL, s4.temperature/(1+s4.rainfall), NULL)) AS o_tr_ratio,
			(SUM(IF(a4.id IS NOT NULL AND s4.premium = 1, 1, 0))+1)/(SUM(IF(a4.id IS NOT NULL AND s4.premium = 0, 1, 0))+1) AS o_pn_ratio,
			MIN(IF(a4.id IS NOT NULL, DATEDIFF(c.session_date, ADDDATE(s4.date, INTERVAL IF(s4.slot_id = 4, 1, 0) DAY)), NULL)) AS o_previous,
			c.remaining,
			c.premium,
			c.hotspot,
			c.created
		FROM (
			SELECT
				b.id,
				b.session_id,
				b.session_date,
				b.location_id,
				b.user_id,
				b.l_count,
				b.l_score,
				b.l_tr_ratio,
				b.l_pn_ratio,
				b.l_previous,
				COUNT(a3.id) AS g_count,
				SUM(IF(a3.id IS NOT NULL, 1/ABS(DATEDIFF(b.session_date, ADDDATE(s3.date, INTERVAL IF(s3.slot_id = 4, 1, 0) DAY))), 0)) AS g_score,
				AVG(IF(a3.id IS NOT NULL AND s3.temperature IS NOT NULL AND s3.rainfall IS NOT NULL, s3.temperature/(1+s3.rainfall), NULL)) AS g_tr_ratio,
				(SUM(IF(a3.id IS NOT NULL AND s3.premium = 1, 1, 0))+1)/(SUM(IF(a3.id IS NOT NULL AND s3.premium = 0, 1, 0))+1) AS g_pn_ratio,
				MIN(IF(a3.id IS NOT NULL, DATEDIFF(b.session_date, ADDDATE(s3.date, INTERVAL IF(s3.slot_id = 4, 1, 0) DAY)), NULL)) AS g_previous,
				b.remaining,
				b.premium,
				b.hotspot,
				b.created
			FROM (
				SELECT
					a.id,
					s.id AS session_id,
					ADDDATE(s.date, INTERVAL IF(s.slot_id = 4, 1, 0) DAY) AS session_date,
					s.location_id,
					a.user_id,
					COUNT(a2.id) AS l_count,
					SUM(IF(a2.id IS NOT NULL, 1/ABS(DATEDIFF(ADDDATE(s.date, INTERVAL IF(s.slot_id = 4, 1, 0) DAY), ADDDATE(s2.date, INTERVAL IF(s2.slot_id = 4, 1, 0) DAY))), 0)) AS l_score,
					AVG(IF(a2.id IS NOT NULL AND s2.temperature IS NOT NULL AND s2.rainfall IS NOT NULL, s2.temperature/(1+s2.rainfall), NULL)) AS l_tr_ratio,
					(SUM(IF(a2.id IS NOT NULL AND s2.premium = 1, 1, 0))+1)/(SUM(IF(a2.id IS NOT NULL AND s2.premium = 0, 1, 0))+1) AS l_pn_ratio,
					MIN(IF(a2.id IS NOT NULL, DATEDIFF(ADDDATE(s.date, INTERVAL IF(s.slot_id = 4, 1, 0) DAY), ADDDATE(s2.date, INTERVAL IF(s2.slot_id = 4, 1, 0) DAY)), NULL)) AS l_previous,
					TIMEDIFF(ADDDATE(ADDTIME(s.date, s.begin), INTERVAL IF(s.slot_id = 4, 1, 0) DAY), NOW()) AS remaining,
					s.premium,
					l.hotspot,
					a.created
				FROM sessions AS s
				JOIN locations AS l ON (l.id = s.location_id)
				JOIN applications AS a ON (a.session_id = s.id AND a.canceled IS NULL)
				LEFT JOIN sessions AS s2 ON (s2.id != s.id AND s2.location_id = s.location_id AND s2.slot_id IN (2, 3) AND s2.application_id IS NOT NULL AND s2.locked IS NULL)
				LEFT JOIN applications AS a2 ON (a2.id = s2.application_id AND a2.user_id = a.user_id AND (a2.canceled IS NULL OR TIMESTAMPDIFF(DAY, a2.canceled, ADDDATE(ADDTIME(s2.date, s2.begin), INTERVAL IF(s2.slot_id = 4, 1, 0) DAY)) < 1))
				WHERE s.id = 12
				GROUP BY a.id
				ORDER BY NULL
				LIMIT 0, 1000000
			) AS b
			LEFT JOIN sessions AS s3 ON (s3.id != b.session_id AND s3.application_id IS NOT NULL AND s3.locked IS NULL)
			LEFT JOIN applications AS a3 ON (a3.id = s3.application_id AND a3.user_id = b.user_id AND (a3.canceled IS NULL OR TIMESTAMPDIFF(DAY, a3.canceled, ADDDATE(ADDTIME(s3.date, s3.begin), INTERVAL IF(s3.slot_id = 4, 1, 0) DAY)) < 1))
			GROUP BY b.id
			ORDER BY NULL
			LIMIT 0, 1000000
		) AS c
		LEFT JOIN sessions AS s4 ON (s4.id != c.session_id AND s4.location_id = c.location_id AND s4.application_id IS NOT NULL AND s4.locked IS NULL)
		LEFT JOIN applications AS a4 ON (a4.id = s4.application_id AND a4.user_id != c.user_id AND (a4.canceled IS NULL OR TIMESTAMPDIFF(DAY, a4.canceled, ADDDATE(ADDTIME(s4.date, s4.begin), INTERVAL IF(s4.slot_id = 4, 1, 0) DAY)) < 1))
		GROUP BY c.id
		ORDER BY NULL
		LIMIT 0, 1000000
	) AS d
	LEFT JOIN groups_users AS gu ON (gu.user_id = d.user_id)
	GROUP BY d.id
	LIMIT 0, 1000000
) AS e
WHERE
	IF(e.group_id <= 2 AND e.l_previous <= 30, e.remaining <= SEC_TO_TIME(2*24*3600), 1) AND
	IF(e.group_id <= 3 AND e.premium = 1 AND e.hotspot = 1, e.remaining <= SEC_TO_TIME(3*24*3600), 1) AND
	IF(e.group_id <= 4 AND e.l_count <= 5, e.remaining <= SEC_TO_TIME(3*24*3600), 1) AND
	IF(e.group_id <= 4 AND e.l_pn_ratio >= 1, e.remaining <= SEC_TO_TIME(3*24*3600), 1) AND
	IF(e.group_id <= 4 AND e.l_tr_ratio >= (e.o_tr_ratio + 5), e.remaining <= SEC_TO_TIME(3*24*3600), 1)
ORDER BY e.l_score, e.g_score, e.created, e.user_id

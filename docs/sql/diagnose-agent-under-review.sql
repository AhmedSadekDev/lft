-- =============================================================================
-- تشخيص: «تحت المراجعة» في /dashboard/agents vs «بانتظار المراجعة» في المصاريف
-- Read-only — شغّل الملف كاملاً مرة واحدة في phpMyAdmin.
--
-- عدّل اسم القاعدة هنا لو مختلف، وعدّل @agent_id لو المندوب مش 28.
-- ملاحظة: استعلام information_schema اتشال عشان phpMyAdmin ما يحوّلش القاعدة
-- ويطلع Error #1109 Unknown table 'agent_expenses' in information_schema.
-- =============================================================================

USE `cloudtal_leader`;

SET @agent_id := 28;

-- 1) بيانات المندوب (الفعلي في الشاشة = wallet - holds)
SELECT id, name, phone, wallet, email
FROM `agents`
WHERE id = @agent_id;

-- 2) نفس حساب قائمة المندوبين: تحت المراجعة
SELECT 'agents_list_under_review' AS metric,
       COALESCE((
           SELECT SUM(value)
           FROM `agent_expenses`
           WHERE agent_id = @agent_id
             AND voided_at IS NULL
             AND wallet_debited_at IS NULL
             AND delivery_policy_id IS NULL
       ), 0) AS expense_holds,
       COALESCE((
           SELECT SUM(value)
           FROM `money_transfers`
           WHERE transferer_id = @agent_id
             AND transferer_type = 'App\\Models\\Agent'
             AND type = 6
       ), 0) AS vault_pending;

-- 3) نفس حساب شارة «بانتظار المراجعة» في صفحة المصاريف
SELECT 'expenses_page_pending' AS metric,
       COUNT(*) AS cnt,
       COALESCE(SUM(ae.value), 0) AS total
FROM `agent_expenses` ae
WHERE ae.agent_id = @agent_id
  AND ae.voided_at IS NULL
  AND ae.booking_service_id IS NULL
  AND (ae.admin_approval = 0 OR ae.admin_approval IS NULL)
  AND NOT EXISTS (
      SELECT 1 FROM `invoices` i
      WHERE ae.booking_id IS NOT NULL AND i.booking_id = ae.booking_id
  )
  AND NOT EXISTS (
      SELECT 1
      FROM `booking_containers` bc
      JOIN `invoices` i ON i.booking_id = bc.booking_id
      WHERE ae.booking_container_id IS NOT NULL
        AND bc.id = ae.booking_container_id
  )
  AND (
      ae.delivery_policy_id IS NULL
      OR NOT EXISTS (
          SELECT 1 FROM `delivery_policies` dp
          WHERE dp.id = ae.delivery_policy_id AND dp.is_settled = 1
      )
  );

-- 4) التفصيل: كل الحجز في قائمة المندوبين + ليه مش في طابور المراجعة
SELECT
    ae.id,
    ae.value,
    ae.admin_approval,
    ae.wallet_debited_at,
    ae.reviewed_at,
    ae.booking_id,
    ae.booking_container_id,
    ae.booking_service_id,
    ae.delivery_policy_id,
    ae.notes,
    ae.created_at,
    CASE
        WHEN ae.booking_service_id IS NOT NULL THEN 'booking_service_receipt'
        WHEN ae.admin_approval = 1 THEN 'admin_approval=1 لكن wallet_debited_at فاضي'
        WHEN ae.booking_id IS NOT NULL AND EXISTS (
            SELECT 1 FROM `invoices` i WHERE i.booking_id = ae.booking_id
        ) THEN 'الحجز عليه فاتورة → مستبعد من awaitingReview'
        WHEN ae.booking_container_id IS NOT NULL AND EXISTS (
            SELECT 1
            FROM `booking_containers` bc
            JOIN `invoices` i ON i.booking_id = bc.booking_id
            WHERE bc.id = ae.booking_container_id
        ) THEN 'حاوية الحجز عليها فاتورة → مستبعد من awaitingReview'
        WHEN ae.admin_approval = 0 OR ae.admin_approval IS NULL THEN 'ظاهر في بانتظار المراجعة (طبيعي)'
        ELSE 'سبب آخر'
    END AS why
FROM `agent_expenses` ae
WHERE ae.agent_id = @agent_id
  AND ae.voided_at IS NULL
  AND ae.wallet_debited_at IS NULL
  AND ae.delivery_policy_id IS NULL
ORDER BY ae.id;

-- 5) ملخص السبب
SELECT
    CASE
        WHEN ae.booking_service_id IS NOT NULL THEN 'booking_service'
        WHEN ae.admin_approval = 1 THEN 'approved_but_not_debited'
        WHEN (
            (ae.booking_id IS NOT NULL AND EXISTS (SELECT 1 FROM `invoices` i WHERE i.booking_id = ae.booking_id))
            OR (ae.booking_container_id IS NOT NULL AND EXISTS (
                SELECT 1 FROM `booking_containers` bc
                JOIN `invoices` i ON i.booking_id = bc.booking_id
                WHERE bc.id = ae.booking_container_id
            ))
        ) THEN 'hidden_because_invoice'
        ELSE 'visible_pending_ui'
    END AS bucket,
    COUNT(*) AS cnt,
    ROUND(SUM(ae.value), 2) AS total
FROM `agent_expenses` ae
WHERE ae.agent_id = @agent_id
  AND ae.voided_at IS NULL
  AND ae.wallet_debited_at IS NULL
  AND ae.delivery_policy_id IS NULL
GROUP BY bucket
ORDER BY total DESC;

-- 6) تحويلات خزنة معلّقة (لو الـ 20 ألف منها)
SELECT id, value, type, created_at
FROM `money_transfers`
WHERE transferer_id = @agent_id
  AND transferer_type = 'App\\Models\\Agent'
  AND type = 6;

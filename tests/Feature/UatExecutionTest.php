<?php

namespace Tests\Feature;

use App\Enums\PoStatus;
use App\Enums\QadSyncStatus;
use App\Enums\ScheduleStatus;
use App\Models\Company;
use App\Models\DeliveryNote;
use App\Models\Forecast;
use App\Models\Item;
use App\Models\PurchaseOrder;
use App\Models\QadItem;
use App\Models\QadSupplier;
use App\Models\User;
use App\Notifications\PurchaseOrderApprovedNotification;
use App\Notifications\PurchaseOrderSubmittedNotification;
use App\Services\Qad\QadItemService;
use App\Services\Qad\QadSupplierService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Executes the remaining UAT-Matex.docx test cases not already covered by
 * QadEndToEndRoleTest / CrossTenantVisibilityTest — see docs/UAT-Matex.docx
 * for the full scripted scenario each test ID maps to.
 *
 * Same invocation as the other two suites — real dev MySQL, live QAD for
 * the master-data sync cases:
 *   DB_CONNECTION=mysql DB_DATABASE=matex DB_HOST=127.0.0.1 DB_PORT=3306 \
 *   DB_USERNAME=root DB_PASSWORD= php artisan test tests/Feature/UatExecutionTest.php
 */
class UatExecutionTest extends TestCase
{
    use DatabaseTransactions;

    private function rmItem(): Item
    {
        return Item::where('item_number', 'CMWNR00002')->firstOrFail();
    }

    private function makePo(PoStatus $status, int $supplierRmId = 10, int $ohpSupplierId = 8): PurchaseOrder
    {
        $confirmed = in_array($status, [PoStatus::AwaitingPurchasingOk, PoStatus::Confirmed, PoStatus::InProgress, PoStatus::Completed], true);

        $po = PurchaseOrder::create([
            'po_number' => 'DR-UAT-'.Str::upper(Str::random(8)),
            'supplier_rm_id' => $supplierRmId,
            'due_date' => '2026-12-20',
            'status' => $status,
            'created_by' => 2,
        ]);

        $poItem = $po->items()->create([
            'item_id' => $this->rmItem()->id,
            'qty_ordered' => 20,
            'qty_confirmed' => $confirmed ? 20 : null,
        ]);

        $po->schedules()->create([
            'purchase_order_item_id' => $poItem->id,
            'ohp_supplier_id' => $ohpSupplierId,
            'scheduled_date' => '2026-12-05',
            'qty' => 20,
            'qty_confirmed' => $confirmed ? 20 : null,
            'status' => ScheduleStatus::Planned,
        ]);

        return $po->fresh(['items', 'schedules']);
    }

    private function makeDn(ScheduleStatus $scheduleStatus, int $supplierRmId = 10, int $ohpSupplierId = 8): DeliveryNote
    {
        $po = $this->makePo(PoStatus::Confirmed, $supplierRmId, $ohpSupplierId);
        $schedule = $po->schedules->first();
        $schedule->update(['status' => $scheduleStatus, 'rm_sj_number' => 'SJ-UAT-'.Str::random(6)]);

        return DeliveryNote::create([
            'dn_number' => 'DN-'.$po->po_number.'-001',
            'purchase_order_id' => $po->id,
            'delivery_schedule_id' => $schedule->id,
            'purchase_order_item_id' => $schedule->purchase_order_item_id,
            'qty' => 20,
            'delivery_date' => '2026-12-05',
            'rm_sj_number' => $schedule->rm_sj_number,
            'generated_at' => now(),
        ]);
    }

    // ---------------------------------------------------------------
    // AUTH-01 / AUTH-02 / AUTH-06 — real login/logout via known-password
    // temp users (existing seed users' real passwords aren't known here).
    // ---------------------------------------------------------------
    public function test_auth_01_02_06_login_wrong_password_and_logout(): void
    {
        $user = User::create([
            'name' => 'UAT Login Test',
            'email' => 'uat-login-'.Str::random(6).'@example.test',
            'password' => Hash::make('correct-password-123'),
            'role' => 'ppic',
            'company_id' => 1,
            'email_verified_at' => now(),
        ]);

        // AUTH-02: wrong password rejected.
        $this->post(route('login'), ['email' => $user->email, 'password' => 'wrong-password'])
            ->assertSessionHasErrors();
        $this->assertGuest();

        // AUTH-01: correct password logs in and reaches the dashboard.
        $this->post(route('login'), ['email' => $user->email, 'password' => 'correct-password-123'])
            ->assertRedirect(route('dashboard', absolute: false));
        $this->assertAuthenticatedAs($user);

        // AUTH-06: logout ends the session.
        $this->post(route('logout'))->assertRedirect('/');
        $this->assertGuest();
    }

    // ---------------------------------------------------------------
    // USR-06 / USR-07
    // ---------------------------------------------------------------
    public function test_supplier_rm_account_requires_phone_number(): void
    {
        $admin = User::findOrFail(1);

        $this->actingAs($admin)->post(route('admin.users.store'), [
            'name' => 'UAT No Phone RM',
            'email' => 'uat-nophone-'.Str::random(6).'@example.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'supplier_rm',
            'supplier_code' => 'L0254',
        ])->assertSessionHasErrors('phone');

        $email = 'uat-withphone-'.Str::random(6).'@example.test';
        $this->actingAs($admin)->post(route('admin.users.store'), [
            'name' => 'UAT With Phone RM',
            'email' => $email,
            'phone' => '081234567890',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'supplier_rm',
            'supplier_code' => 'L0254',
        ])->assertRedirect(route('admin.users.index'));

        $this->assertSame('081234567890', User::where('email', $email)->value('phone'));

        // Non-supplier-RM roles: phone stays optional.
        $this->actingAs($admin)->post(route('admin.users.store'), [
            'name' => 'UAT No Phone PPIC',
            'email' => 'uat-ppic-nophone-'.Str::random(6).'@example.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'ppic',
        ])->assertRedirect(route('admin.users.index'));
    }

    public function test_supplier_rm_notified_by_email_when_po_submitted(): void
    {
        Notification::fake();

        $po = $this->makePo(PoStatus::Draft);
        $purchasing = User::findOrFail(2);

        $this->actingAs($purchasing)->post(route('purchase-orders.submit', $po))->assertRedirect();

        Notification::assertSentTo(User::findOrFail(11), PurchaseOrderSubmittedNotification::class);
    }

    public function test_whatsapp_channel_included_and_degrades_gracefully_without_token(): void
    {
        $rm = User::findOrFail(11);
        $notification = new PurchaseOrderSubmittedNotification($this->makePo(PoStatus::Draft));

        $this->assertContains(\App\Notifications\Channels\WhatsAppChannel::class, $notification->via($rm));

        // No FONNTE_TOKEN configured in this environment — real (non-faked)
        // send must not throw, just skip with a warning log.
        $rm->forceFill(['phone' => '081234567890'])->save();
        $po = $this->makePo(PoStatus::AwaitingRmConfirm, supplierRmId: 10);
        $purchasing = User::findOrFail(2);

        $this->actingAs($rm)->post(route('purchase-orders.confirm-rm', $po), [
            'items' => [['id' => $po->items->first()->id, 'qty_confirmed' => 20]],
            'schedules' => [['id' => $po->schedules->first()->id, 'qty_confirmed' => 20]],
        ])->assertRedirect();

        $this->actingAs($purchasing)->post(route('purchase-orders.approve', $po))
            ->assertRedirect();
        $this->assertSame(PoStatus::Confirmed, $po->fresh()->status);
    }

    public function test_whatsapp_message_content_renders_correctly(): void
    {
        $po = $this->makePo(PoStatus::AwaitingPurchasingOk);
        $po->load('items.item');
        $rm = User::findOrFail(11);

        $message = (new PurchaseOrderApprovedNotification($po))->toWhatsApp($rm);

        $this->assertStringContainsString($po->po_number, $message);
        $this->assertStringContainsString($po->items->first()->item->item_number, $message);
        $this->assertStringContainsString(route('purchase-orders.show', $po), $message);
    }

    public function test_usr_05_supplier_account_category_must_match_role(): void
    {
        $ohpOnlyCode = QadSupplier::where('category', 'ohp')->value('qad_code');
        if (! $ohpOnlyCode) {
            $this->markTestSkipped('No qad_suppliers row categorized ohp — run the MST sync test first.');
        }
        $admin = User::findOrFail(1);

        // Trying to create a Supplier RM account using an OHP-categorized code must fail.
        $this->actingAs($admin)->post(route('admin.users.store'), [
            'name' => 'UAT Wrong Category',
            'email' => 'uat-wrongcat-'.Str::random(6).'@example.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'supplier_rm',
            'supplier_code' => $ohpOnlyCode,
        ])->assertSessionHasErrors('supplier_code');
    }

    public function test_po_02_due_date_cannot_be_in_the_past(): void
    {
        $purchasing = User::findOrFail(2);

        $this->actingAs($purchasing)->post(route('purchase-orders.store'), [
            'supplier_code' => 'L0254',
            'due_date' => now()->subDay()->format('Y-m-d'),
            'items' => [[
                'item_number' => 'CMWNR00002',
                'qty_ordered' => 10,
                'schedules' => [['ohp_supplier_code' => 'L0061', 'scheduled_date' => now()->format('Y-m-d'), 'qty' => 10]],
            ]],
        ])->assertSessionHasErrors('due_date');
    }

    public function test_usr_06_user_cannot_delete_own_account(): void
    {
        $admin = User::findOrFail(1);
        $this->actingAs($admin)->delete(route('admin.users.destroy', $admin))
            ->assertRedirect();
        $this->assertNotNull($admin->fresh(), 'Own account must survive the self-delete attempt.');
    }

    public function test_usr_07_last_admin_cannot_be_deleted(): void
    {
        // Deliberately fresh admins with zero historical activity — deleting
        // the REAL seed admin (id 1) here surfaced a separate, genuine
        // defect (see UAT report: deleting a user who has ever confirmed an
        // OHP delivery crashes with a raw 500 instead of a friendly error,
        // because ohp_confirmations.confirmed_by has no nullOnDelete/cascade
        // and UserController::destroy() doesn't catch the FK violation).
        // This test isolates the "last admin" business rule from that bug.
        User::where('role', 'admin')->update(['role' => 'purchasing']);
        $adminA = User::create([
            'name' => 'UAT Temp Admin A', 'email' => 'uat-admin-a-'.Str::random(6).'@example.test',
            'password' => Hash::make('x'), 'role' => 'admin', 'company_id' => 1, 'email_verified_at' => now(),
        ]);
        $adminB = User::create([
            'name' => 'UAT Temp Admin B', 'email' => 'uat-admin-b-'.Str::random(6).'@example.test',
            'password' => Hash::make('x'), 'role' => 'admin', 'company_id' => 1, 'email_verified_at' => now(),
        ]);
        $this->assertSame(2, User::where('role', 'admin')->count());

        // With 2 admins, deleting one is fine — count goes to 1.
        $this->actingAs($adminA)->delete(route('admin.users.destroy', $adminB))->assertRedirect();
        $this->assertSame(1, User::where('role', 'admin')->count());

        // The one remaining admin cannot remove themselves (self-delete
        // guard), so the system can never reach zero admins in practice.
        $this->actingAs($adminA)->delete(route('admin.users.destroy', $adminA))->assertRedirect();
        $this->assertSame(1, User::where('role', 'admin')->count(), 'System must always retain at least one admin.');
    }

    /**
     * DEFECT found while exercising USR-07 with the real seed admin (id 1):
     * deleting ANY user who has ever confirmed an OHP delivery crashes with
     * a raw, unhandled 500 (PDOException on ohp_confirmations.confirmed_by)
     * instead of a friendly validation message — because that FK has no
     * nullOnDelete()/cascadeOnDelete() and UserController::destroy() doesn't
     * catch the violation. Reproduced here via a second admin deleting user
     * 1 (self-delete would mask it behind the "own account" guard first).
     * This test intentionally documents CURRENT (broken) behavior.
     */
    public function test_usr_08_deleting_user_with_ohp_confirmation_history_fails_gracefully(): void
    {
        $otherAdmin = User::create([
            'name' => 'UAT Temp Admin', 'email' => 'uat-admin-'.Str::random(6).'@example.test',
            'password' => Hash::make('x'), 'role' => 'admin', 'company_id' => 1, 'email_verified_at' => now(),
        ]);
        $target = User::findOrFail(1);

        // Fixed: UserController::destroy() now catches the FK violation
        // (SQLSTATE 23000) and redirects with a friendly message instead of
        // letting a raw 500 through.
        $this->actingAs($otherAdmin)->delete(route('admin.users.destroy', $target))
            ->assertRedirect();
        $this->assertNotNull($target->fresh(), 'The referenced user must survive the failed delete.');
    }

    // ---------------------------------------------------------------
    // MST-01 / MST-02 / MST-04 / MST-06 / MST-07 / NFR-01 — real QAD sync.
    // ---------------------------------------------------------------
    public function test_mst_01_02_04_06_07_nfr_01_real_qad_master_data_sync(): void
    {
        $itemStart = microtime(true);
        $itemResult = app(QadItemService::class)->sync();
        $itemSeconds = round(microtime(true) - $itemStart, 2);

        $this->assertGreaterThan(0, $itemResult['synced'], 'MST-01: item sync should process a non-zero number of rows.');
        $this->assertGreaterThan(0, QadItem::count());

        $supplierStart = microtime(true);
        $supplierResult = app(QadSupplierService::class)->sync();
        $supplierSeconds = round(microtime(true) - $supplierStart, 2);

        $this->assertGreaterThan(0, $supplierResult['synced'], 'MST-02: supplier sync should process a non-zero number of rows.');
        $this->assertGreaterThan(0, QadSupplier::count());

        fwrite(STDERR, "\n[NFR-01] Item sync: {$itemResult['synced']} rows in {$itemSeconds}s | Supplier sync: {$supplierResult['synced']} rows in {$supplierSeconds}s\n");

        // MST-04: category survives a resync.
        $supplier = QadSupplier::whereNotNull('qad_code')->first();
        $supplier->update(['category' => 'raw_mat']);
        app(QadSupplierService::class)->sync();
        $this->assertSame('raw_mat', $supplier->fresh()->category?->value ?? $supplier->fresh()->category);

        // MST-06: item picker for PO creation only returns prod_line = RM.
        $purchasing = User::findOrFail(2);
        $qadItems = $this->actingAs($purchasing)->get(route('purchase-orders.create'))->inertiaProps('qadItems');
        $this->assertNotEmpty($qadItems);
        foreach ($qadItems as $row) {
            $this->assertSame('RM', $row['prod_line']);
        }

        // MST-07: supplier picker only returns suppliers with a portal account.
        $qadSuppliers = $this->actingAs($purchasing)->get(route('purchase-orders.create'))->inertiaProps('qadSuppliers');
        $this->assertNotEmpty($qadSuppliers);
        foreach ($qadSuppliers as $row) {
            $hasAccount = Company::where('code', $row['qad_code'])->whereHas('users')->exists();
            $this->assertTrue($hasAccount, "Supplier {$row['qad_code']} shown in picker but has no portal account.");
        }
    }

    // ---------------------------------------------------------------
    // MST-03 — manual RM/OHP categorization.
    // ---------------------------------------------------------------
    public function test_mst_03_manual_supplier_categorization(): void
    {
        $supplier = QadSupplier::first();
        if (! $supplier) {
            $this->markTestSkipped('No qad_suppliers row available — run MST-01/02 sync first.');
        }
        $admin = User::findOrFail(1);

        $this->actingAs($admin)
            ->patch(route('admin.qad-suppliers.update-category', $supplier), ['category' => 'ohp'])
            ->assertRedirect();
        $this->assertSame('ohp', $supplier->fresh()->category?->value ?? $supplier->fresh()->category);
    }

    // ---------------------------------------------------------------
    // PO-17 — QAD failure does not block Purchasing approval.
    // ---------------------------------------------------------------
    public function test_po_17_qad_failure_does_not_block_approval(): void
    {
        Config::set('qad.qxi.base_url', 'http://127.0.0.1:1/unreachable');

        $po = $this->makePo(PoStatus::AwaitingPurchasingOk);
        $purchasing = User::findOrFail(2);

        $this->actingAs($purchasing)->post(route('purchase-orders.approve', $po))->assertRedirect();

        $po->refresh();
        $this->assertSame(PoStatus::Confirmed, $po->status, 'Approval must still succeed even when QAD is unreachable.');
        $this->assertSame(QadSyncStatus::Failed, $po->qad_status);
    }

    // ---------------------------------------------------------------
    // DN-05 — defensive validation: shipment confirm requires an SJ number.
    // Not reachable via the normal UI (generateDn already requires it), but
    // the backend guard itself must still hold if ever invoked directly.
    // ---------------------------------------------------------------
    public function test_dn_05_confirm_shipment_requires_sj_number(): void
    {
        $dn = $this->makeDn(ScheduleStatus::Planned);
        $dn->deliverySchedule->update(['rm_sj_number' => null]);
        $dn->update(['rm_sj_number' => null]);
        $rm = User::findOrFail(11);

        // The "SJ required" rule is actually enforced one layer up, at
        // DeliveryNotePolicy::confirmShipment() — an empty SJ number makes
        // the policy deny outright (403), so the action's own internal
        // ValidationException check is an unreachable defensive fallback
        // in current practice. Documenting the real enforcement point here.
        $this->actingAs($rm)->post(route('delivery-notes.confirm-shipment', $dn))
            ->assertForbidden();
    }

    // ---------------------------------------------------------------
    // DN-09 — file validation on OHP confirmation.
    // ---------------------------------------------------------------
    public function test_dn_09_ohp_confirm_rejects_bad_file(): void
    {
        Storage::fake('public');
        $dn = $this->makeDn(ScheduleStatus::ShipConfirmed);
        $ohp = User::findOrFail(10);

        $this->actingAs($ohp)->post(route('delivery-notes.confirm-ohp', $dn), [
            'sj_document' => UploadedFile::fake()->create('not-allowed.docx', 10, 'application/msword'),
        ])->assertSessionHasErrors('sj_document');

        $this->actingAs($ohp)->post(route('delivery-notes.confirm-ohp', $dn), [
            'sj_document' => UploadedFile::fake()->create('too-big.pdf', 6000, 'application/pdf'),
        ])->assertSessionHasErrors('sj_document');
    }

    // ---------------------------------------------------------------
    // RCV-05 — receiving qty cannot exceed the DN's remaining qty.
    // ---------------------------------------------------------------
    public function test_rcv_05_receive_qty_cannot_exceed_remaining(): void
    {
        $dn = $this->makeDn(ScheduleStatus::OhpOk);
        $ppic = User::findOrFail(3);

        $this->actingAs($ppic)->post(route('receivings.store', $dn), ['received_qty' => 999])
            ->assertSessionHasErrors('received_qty');
        $this->assertSame(0, $dn->receivings()->count());
    }

    // ---------------------------------------------------------------
    // EML-01 — every Supplier RM user on the company gets the email.
    // ---------------------------------------------------------------
    public function test_eml_01_all_rm_users_on_company_are_notified(): void
    {
        Notification::fake();

        $secondRmUser = User::create([
            'name' => 'UAT Second RM Contact',
            'email' => 'uat-rm2-'.Str::random(6).'@example.test',
            'password' => Hash::make('x'),
            'role' => 'supplier_rm',
            'company_id' => 10, // same company as user 11 (L0254)
            'email_verified_at' => now(),
        ]);

        $po = $this->makePo(PoStatus::AwaitingPurchasingOk);
        $purchasing = User::findOrFail(2);
        $this->actingAs($purchasing)->post(route('purchase-orders.approve', $po));

        Notification::assertSentTo(User::findOrFail(11), PurchaseOrderApprovedNotification::class);
        Notification::assertSentTo($secondRmUser, PurchaseOrderApprovedNotification::class);
    }

    // ---------------------------------------------------------------
    // EML-03 — a mail failure must not block the approval.
    // ---------------------------------------------------------------
    public function test_eml_03_mail_failure_does_not_block_approval(): void
    {
        Config::set('mail.default', 'uat-broken-mailer-does-not-exist');
        Log::spy();

        $po = $this->makePo(PoStatus::AwaitingPurchasingOk);
        $purchasing = User::findOrFail(2);

        $this->actingAs($purchasing)->post(route('purchase-orders.approve', $po))->assertRedirect();

        $po->refresh();
        $this->assertSame(PoStatus::Confirmed, $po->status, 'Approval must succeed even if the mail driver is broken.');
        Log::shouldHaveReceived('error')->withArgs(fn ($msg, $context = []) => str_contains($msg, 'Failed to notify Supplier RM'))->atLeast()->once();
    }

    // ---------------------------------------------------------------
    // FC-01..FC-04 — Forecast module.
    // ---------------------------------------------------------------
    public function test_fc_01_purchasing_can_upload_forecast(): void
    {
        Storage::fake('public');
        $purchasing = User::findOrFail(2);
        $period = now()->addMonthsNoOverflow(3)->format('Y-m');

        $this->actingAs($purchasing)->post(route('forecasts.store'), [
            'supplier_rm_id' => 10,
            'period_month' => $period,
            'notes' => 'UAT forecast upload',
            'file' => UploadedFile::fake()->create('forecast.xlsx', 50, 'application/vnd.ms-excel'),
            'items' => [['item_id' => $this->rmItem()->id, 'qty' => 500]],
        ])->assertRedirect();

        $this->assertTrue(Forecast::where('supplier_rm_id', 10)->whereDate('period_month', $period.'-01')->exists());
    }

    public function test_fc_02_03_supplier_rm_sees_only_own_forecast_and_cannot_manage(): void
    {
        $period = now()->addMonthsNoOverflow(4)->startOfMonth();
        $own = Forecast::create(['supplier_rm_id' => 10, 'period_month' => $period, 'uploaded_by' => 2, 'notes' => 'UAT own']);
        $other = Forecast::create(['supplier_rm_id' => 12, 'period_month' => $period, 'uploaded_by' => 2, 'notes' => 'UAT other']);

        $rm = User::findOrFail(11); // company 10

        $ids = collect($this->actingAs($rm)->get(route('forecasts.index'))->inertiaProps('forecasts.data'))->pluck('id');
        $this->assertContains($own->id, $ids);
        $this->assertNotContains($other->id, $ids);

        // FC-03: RM cannot view another company's forecast directly, and cannot create one.
        $this->actingAs($rm)->get(route('forecasts.show', $other))->assertForbidden();
        $this->actingAs($rm)->get(route('forecasts.create'))->assertForbidden();
    }

    public function test_fc_04_forecast_download_authorization(): void
    {
        Storage::fake('public');
        $path = 'forecasts/uat-test.xlsx';
        Storage::disk('public')->put($path, 'dummy content');
        $period = now()->addMonthsNoOverflow(5)->startOfMonth();
        $forecast = Forecast::create([
            'supplier_rm_id' => 10, 'period_month' => $period, 'uploaded_by' => 2,
            'file_path' => $path, 'original_filename' => 'uat-test.xlsx',
        ]);

        $owner = User::findOrFail(11); // company 10
        $other = User::findOrFail(12); // company 12

        $this->actingAs($owner)->get(route('forecasts.download', $forecast))->assertOk();
        $this->actingAs($other)->get(route('forecasts.download', $forecast))->assertForbidden();
    }

    // ---------------------------------------------------------------
    // BIL-03 — Supplier OHP has no access to Billing.
    // ---------------------------------------------------------------
    public function test_bil_03_supplier_ohp_blocked_from_billing(): void
    {
        $ohp = User::findOrFail(10);
        $this->actingAs($ohp)->get(route('billing.index'))->assertForbidden();
    }

    public function test_signed_po_upload_and_visibility(): void
    {
        Storage::fake('public');

        $po = $this->makePo(PoStatus::Confirmed); // supplier_rm_id=10, ohp_supplier_id=8
        $purchasing = User::findOrFail(2);
        $rmOwner = User::findOrFail(11); // company 10
        $rmOther = User::findOrFail(12); // company 12
        $ohp = User::findOrFail(10); // scheduled on this PO (company 8) but must NOT see the signed PO
        $ppic = User::findOrFail(3);

        // Wrong roles/status can't upload.
        foreach ([$rmOwner, $ohp, $ppic] as $user) {
            $this->actingAs($user)
                ->post(route('purchase-orders.upload-signed-po', $po), [
                    'signed_po' => UploadedFile::fake()->create('signed.pdf', 100, 'application/pdf'),
                ])
                ->assertForbidden();
        }

        $draftPo = $this->makePo(PoStatus::Draft);
        $this->actingAs($purchasing)
            ->post(route('purchase-orders.upload-signed-po', $draftPo), [
                'signed_po' => UploadedFile::fake()->create('signed.pdf', 100, 'application/pdf'),
            ])
            ->assertForbidden();

        // Non-PDF rejected.
        $this->actingAs($purchasing)
            ->post(route('purchase-orders.upload-signed-po', $po), [
                'signed_po' => UploadedFile::fake()->create('signed.docx', 100, 'application/msword'),
            ])
            ->assertSessionHasErrors('signed_po');

        // Purchasing uploads successfully.
        $this->actingAs($purchasing)
            ->post(route('purchase-orders.upload-signed-po', $po), [
                'signed_po' => UploadedFile::fake()->create('signed.pdf', 100, 'application/pdf'),
            ])
            ->assertRedirect();

        $po->refresh();
        $this->assertTrue($po->has_signed_po);
        $this->assertNotNull($po->signed_po_uploaded_at);
        $this->assertSame($purchasing->id, $po->signed_po_uploaded_by);

        // Owning RM can download; a different RM company cannot.
        $this->actingAs($rmOwner)->get(route('purchase-orders.download-signed-po', $po))->assertOk();
        $this->actingAs($rmOther)->get(route('purchase-orders.download-signed-po', $po))->assertForbidden();

        // Supplier OHP is deliberately excluded even though scheduled on this PO.
        $this->actingAs($ohp)->get(route('purchase-orders.download-signed-po', $po))->assertForbidden();

        // Staff can always view.
        $this->actingAs($ppic)->get(route('purchase-orders.download-signed-po', $po))->assertOk();
    }

    // ---------------------------------------------------------------
    // DSH-01 — Supplier RM dashboard stats match actual DB state.
    // ---------------------------------------------------------------
    public function test_dsh_01_supplier_rm_dashboard_stats_are_accurate(): void
    {
        $rm = User::findOrFail(11); // company 10
        $this->makePo(PoStatus::AwaitingRmConfirm); // counts toward "to_confirm"
        $this->makePo(PoStatus::AwaitingRmConfirm);

        $stats = $this->actingAs($rm)->get(route('dashboard'))->inertiaProps('stats');

        $expected = PurchaseOrder::where('supplier_rm_id', 10)->where('status', PoStatus::AwaitingRmConfirm)->count();
        $this->assertSame($expected, $stats['to_confirm']);
    }
}

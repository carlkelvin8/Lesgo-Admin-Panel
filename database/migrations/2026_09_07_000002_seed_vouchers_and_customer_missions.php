<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Seed vouchers (same as API)
        $vouchers = [
            ['code'=>'WELCOME10','title'=>'Welcome Discount','description'=>'10% off your first order','discount_text'=>'10% OFF','min_order'=>'₱100','type'=>'percentage','value'=>10,'max_discount'=>50,'min_order_value'=>100,'max_uses'=>1000,'expires_at'=>'2026-12-31','user_restrictions'=>json_encode(['new_users_only'=>true]),'applicable_services'=>null,'is_active'=>1],
            ['code'=>'SAVE20','title'=>'Save Big','description'=>'₱20 off orders above ₱150','discount_text'=>'₱20 OFF','min_order'=>'₱150','type'=>'fixed','value'=>20,'max_discount'=>null,'min_order_value'=>150,'max_uses'=>500,'expires_at'=>'2026-06-30','user_restrictions'=>json_encode([]),'applicable_services'=>null,'is_active'=>1],
            ['code'=>'FREEDEL','title'=>'Free Delivery','description'=>'Free delivery on orders above ₱200','discount_text'=>'FREE DELIVERY','min_order'=>'₱200','type'=>'free_delivery','value'=>0,'max_discount'=>null,'min_order_value'=>200,'max_uses'=>null,'expires_at'=>null,'user_restrictions'=>json_encode([]),'applicable_services'=>json_encode([1,2]),'is_active'=>1],
            ['code'=>'RIDE20','title'=>'Save on your next ride','description'=>'Get 20% off LesRide bookings around Cagayan de Oro.','discount_text'=>'20% OFF','min_order'=>'₱100','type'=>'percentage','value'=>20,'max_discount'=>80,'min_order_value'=>100,'max_uses'=>null,'expires_at'=>'2026-12-31','user_restrictions'=>json_encode([]),'applicable_services'=>json_encode([2]),'is_active'=>1],
            ['code'=>'LUNCHFREE','title'=>'Free delivery for lunch','description'=>'Order from partner restaurants and skip the delivery fee.','discount_text'=>'FREE DELIVERY','min_order'=>'₱150','type'=>'free_delivery','value'=>0,'max_discount'=>null,'min_order_value'=>150,'max_uses'=>null,'expires_at'=>'2026-12-31','user_restrictions'=>json_encode([]),'applicable_services'=>json_encode([3]),'is_active'=>1],
            ['code'=>'PAYBACK','title'=>'LesPay cashback','description'=>'Pay with LesPay wallet and receive cashback credit.','discount_text'=>'P50 BACK','min_order'=>'₱200','type'=>'fixed','value'=>50,'max_discount'=>null,'min_order_value'=>200,'max_uses'=>null,'expires_at'=>'2026-12-31','user_restrictions'=>json_encode([]),'applicable_services'=>null,'is_active'=>1],
        ];
        foreach ($vouchers as $v) {
            DB::table('vouchers')->updateOrInsert(['code'=>$v['code']], array_merge($v, ['created_at'=>now(),'updated_at'=>now()]));
        }

        // Seed customer mission templates if not exist
        $templates = [
            ['title'=>'Book 1 LesEat today','description'=>'Order food from any restaurant','type'=>'daily','target_audience'=>'customer','goal_type'=>'leseat_order','goal_target'=>1,'reward_amount'=>50.00,'reward_currency'=>'PHP','service_code'=>null,'is_active'=>1],
            ['title'=>'Refer 1 friend','description'=>'Invite a friend to join LesGo','type'=>'daily','target_audience'=>'customer','goal_type'=>'friend_referral','goal_target'=>1,'reward_amount'=>100.00,'reward_currency'=>'PHP','service_code'=>null,'is_active'=>1],
            ['title'=>'Leave us a review','description'=>'Rate our app on the store','type'=>'daily','target_audience'=>'customer','goal_type'=>'app_review','goal_target'=>1,'reward_amount'=>25.00,'reward_currency'=>'PHP','service_code'=>null,'is_active'=>1],
            ['title'=>'Like our FB page','description'=>'Follow us on social media','type'=>'daily','target_audience'=>'customer','goal_type'=>'social_follow','goal_target'=>1,'reward_amount'=>25.00,'reward_currency'=>'PHP','service_code'=>null,'is_active'=>1],
            ['title'=>'Book 5 LesRide','description'=>'Complete 5 ride bookings','type'=>'daily','target_audience'=>'customer','goal_type'=>'lesride_orders','goal_target'=>5,'reward_amount'=>200.00,'reward_currency'=>'PHP','service_code'=>null,'is_active'=>1],
        ];
        foreach ($templates as $t) {
            DB::table('mission_templates')->updateOrInsert(
                ['title'=>$t['title'],'target_audience'=>$t['target_audience']],
                array_merge($t, ['created_at'=>now(),'updated_at'=>now()])
            );
        }
    }

    public function down(): void
    {
        // Keep data on rollback for safety
    }
};

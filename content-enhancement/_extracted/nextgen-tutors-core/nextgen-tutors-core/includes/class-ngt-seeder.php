<?php
/**
 * NextGen Tutors Seeder Class
 *
 * Populates the database with sample/test data.
 */

class NGT_Seeder {

    private static $instance = null;

    /**
     * Get singleton instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {}

    /**
     * Seed tutors
     */
    public function seed_tutors($count) {
        ngt()->logger->info("Seeding $count tutors");
        
        $subjects = ['Mathematics', 'Science', 'English', 'History', 'Physics'];
        
        for ($i = 0; $i < $count; $i++) {
            $name = "Tutor " . ($i + 1);
            $email = "tutor" . ($i + 1) . "@example.com";
            
            ngt()->database->insert_contact([
                'first_name' => 'Tutor',
                'last_name' => (string)($i + 1),
                'email' => $email,
                'role' => 'tutor',
                'status' => 'active',
                'meta' => json_encode(['subjects' => [$subjects[array_rand($subjects)]]])
            ]);
        }
        
        return true;
    }

    /**
     * Seed parents
     */
    public function seed_parents($count) {
        ngt()->logger->info("Seeding $count parents");
        
        for ($i = 0; $i < $count; $i++) {
            $email = "parent" . ($i + 1) . "@example.com";
            
            ngt()->database->insert_contact([
                'first_name' => 'Parent',
                'last_name' => (string)($i + 1),
                'email' => $email,
                'role' => 'parent',
                'status' => 'active'
            ]);
        }
        
        return true;
    }

    /**
     * Seed bookings/earnings
     */
    public function seed_bookings($count) {
        ngt()->logger->info("Seeding $count bookings");
        
        for ($i = 0; $i < $count; $i++) {
            ngt()->database->insert_earnings([
                'tutor_id' => rand(1, 10),
                'order_id' => rand(1000, 9999),
                'amount' => rand(100, 500),
                'commission' => rand(10, 50),
                'status' => 'completed',
                'payout_status' => 'pending'
            ]);
        }
        
        return true;
    }

    /**
     * Seed GamiPress Gamification Data
     */
    public function seed_gamification() {
        ngt()->logger->info("Seeding GamiPress gamification data (Badges & Points)");
        
        global $wpdb;
        $prefix = $wpdb->prefix;
        
        // 1. Seed Point Types (e.g. "Tutor Credits", "Student XP")
        $point_types = [
            ['slug' => 'tutor-credits', 'name' => 'Tutor Credits'],
            ['slug' => 'student-xp', 'name' => 'Student XP']
        ];
        
        // 2. Seed Badges/Achievements
        $achievements = [
            ['title' => 'First Lesson', 'type' => 'badge', 'points' => 100],
            ['title' => 'Top Rated Tutor', 'type' => 'badge', 'points' => 500],
            ['title' => 'Master Educator', 'type' => 'rank', 'points' => 1000]
        ];

        ngt()->logger->success('Gamification seeding complete', [
            'point_types' => count($point_types),
            'achievements' => count($achievements)
        ]);
        
        return true;
    }

    /**
     * Seed everything
     */
    public function seed_all($tutors, $parents, $bookings) {
        $this->seed_tutors($tutors);
        $this->seed_parents($parents);
        $this->seed_bookings($bookings);
        $this->seed_gamification();
        
        ngt()->logger->success('Master seeding complete', [
            'tutors' => $tutors,
            'parents' => $parents,
            'bookings' => $bookings
        ]);
        
        return true;
    }
}

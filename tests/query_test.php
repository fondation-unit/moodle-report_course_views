<?php // File: mod/myplugin/tests/complex_test.php

namespace report_visits;

class query_test extends \advanced_testcase {
    public function test_isadmin() {
        global $DB;

        $this->resetAfterTest(true);          // reset all changes automatically after this test

        $this->assertFalse(is_siteadmin());   // by default no user is logged-in
        $this->setUser(2);                    // switch $USER
        $this->assertTrue(is_siteadmin());    // admin is logged-in now

        $DB->delete_records('user', array()); // lets do something crazy

        $this->resetAllData();                // that was not a good idea, let's go back
        $this->assertTrue($admin = $DB->record_exists('user', array('id' => 2)));
        $this->assertFalse(is_siteadmin());
    }
}

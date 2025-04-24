<?php // File: report/visits/tests/capability_test.php

namespace report_visits;

class capability_test extends \advanced_testcase {
    public function test_has_capability() {
        // Reset all changes after this test.
        $this->resetAfterTest(true);

        $this->setAdminUser();
        $this->assertTrue(is_siteadmin());

        $systemcontext = \context_system::instance();
        $canviewreports = has_capability('report/visits:view', $systemcontext);
        $this->assertTrue($canviewreports);
    }
}

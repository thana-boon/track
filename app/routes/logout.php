<?php
declare(strict_types=1);

activity_log_write('logout', []);
auth_logout();
flash_set('success', 'ออกจากระบบแล้ว');
redirect('login');

<?php
declare(strict_types=1);

auth_logout();
flash_set('success', 'ออกจากระบบแล้ว');
redirect('login');

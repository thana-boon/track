f = open('c:/xampp/htdocs/tracks/app/views/class_attendance/create.php','rb')
content = f.read().decode('utf-8')
f.close()

OLD = "          <textarea\r\n            name=\"student_codes_text\"\r\n            rows=\"10\"\r\n            placeholder=\"\u0e15\u0e31\u0e27\u0e2d\u0e22\u0e48\u0e32\u0e07:&#10;65001&#10;\u0e2a\u0e21\u0e0a\u0e32\u0e22 \u0e43\u0e08\u0e14\u0e35 65002&#10;65003\"\r\n            class=\"mt-3 w-full rounded-2xl border border-black/10 bg-white px-3 py-2.5 text-sm outline-none focus:border-calm-500\"\r\n          ><?= e((string)($studentCodesText ?? '')) ?></textarea>"

print('FOUND:', OLD in content)

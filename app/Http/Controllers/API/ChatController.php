<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use OpenAI\Laravel\Facades\OpenAI;

class ChatController extends Controller
{


    protected $dbSchema = <<<EOD
                            Table: housing_units
                            Columns:
                            - id: BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
                            - name: VARCHAR(255) NOT NULL
                            - price: DECIMAL(10, 2) NOT NULL
                            - location: VARCHAR(255) NOT NULL
                            - features: VARCHAR(255) NOT NULL ('wifi', 'parking', 'gym',etc)
                            - rooms: INTEGER NOT NULL
                            - description: TEXT
                            - created_at: TIMESTAMP NULL
                            - updated_at: TIMESTAMP NULL

                            EOD;


    public function smartChat(Request $request)
    {
        $userMessage = $request->message;

        $greetings = ['هلا',
                      'أهلاً', 'مرحباً', 'السلام عليكم', 'صباح الخير', 'مساء الخير', 'كيف حالك؟',
                      'أهلاً وسهلاً', 'صباح النور', 'مساء النور', 'مرحبا بك', 'تحية طيبة', 'أهلاً بك',
                      'كيف حالكم؟', 'السلام عليكم ورحمة الله وبركاته', 'صباح الورد', 'مساء الورد', 'أهلاً بالصديق',
                      'Hello', 'Hi', 'Hey', 'Good morning', 'Good afternoon', 'Good evening', 'How are you?', 'Howdy',
                      'Greetings', "What's up?", 'Welcome', 'Morning', 'Afternoon', 'Evening', 'Hi there', 'Yo', "How's it going?",
                      "What's going on?", 'Good day', 'Salutations'
        ];
        foreach ($greetings as $greeting) {
            if (stripos($userMessage, $greeting) !== false) {
                return response()->json([
                    'reply' => 'مرحباً! كيف يمكنني مساعدتك اليوم؟ 😊'
                ]);
            }
        }

        try {
            $response    = Http::withToken('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')->post('https://openrouter.ai/api/v1/chat/completions', [
                'model'    => 'mistralai/mistral-7b-instruct',
                'messages' => [
                    [
                        'role' => 'system',

                        'content' => <<<EOD
أنت روبوت دردشة عقاري تتحدث العربية، مهمتك مساعدة المستخدمين في إيجاد وحدات سكنية عن طريق توليد استعلامات SQL بناءً على طلباتهم، حسب المخطط التالي لقاعدة البيانات:
{$this->dbSchema}

التعليمات:
- رد على طلبات المستخدم بنبرة ودية ومساعدة.
- لا تقم بترجمة أو تعديل اسم location الذي يكتبه المستخدم، استخدمه كما هو مرسل داخل  الاستعلام.
-ابحث  location بالعربي وكمان الانجليزي
- If the user asks for properties (e.g., "houses in الرياض under $500k" or "apartments around $1000"), generate a single SQL SELECT query to fetch matching records from the properties table.
- Format the query in a code block: sql\nYOUR_QUERY\n

- Ensure queries are safe (no injections, proper SQL syntax) and select only relevant columns: id, name, price, location, rooms.

- استخدم تطابق مرن:

  - عدد الغرف: استخدم تطابق دقيق، مثلاً "3 غرف" تصبح rooms = 3.
  - السعر:
    - إذا قال المستخدم "حوالي" أو "قرابة"، استخدم نطاق ±25٪ (مثلاً "حوالي 1000" تعني BETWEEN 750 AND 1250).
    - إذا قال "أقل من" أو "تحت"، استخدم price < القيمة.
    - إذا قال "أكثر من" أو "فوق"، استخدم price > القيمة.
    - إذا لم يُحدد طريقة للسعر، استخدم نطاق تقريبي ±25٪ تلقائياً.
- حد النتائج يجب أن يكون 5 صفوف فقط باستخدام LIMIT 5.
- إذا كان الطلب غير واضح أو غير متعلق بالعقارات (مثل "مرحباً")، رد بشكل ودّي واطلب توضيح (مثل "من فضلك حدد الموقع أو الميزانية").
- إذا لم يكن هناك نتائج متوقعة (مثل "في نارنيا")، أرفق الاستعلام ووضح أن النتائج قد تكون غير متاحة واقترح موقعاً حقيقياً مثل "ميامي".
- لا تنشئ استعلامات DELETE أو UPDATE أو INSERT — فقط SELECT.

EOD
                    ],
                    ['role' => 'user', 'content' => $userMessage],
                ],
            ]);
            $botResponse = $response['choices'][0]['message']['content'] ?? 'Sorry, I couldn’t understand your request.';
            if (!preg_match('/```sql\n(.+?)\n```/s', $botResponse)) {
                return response()->json([
                    'reply' => 'لم أتمكن من فهم طلبك بشكل صحيح. من فضلك وضّح الموقع أو السعر أو عدد الغرف المطلوبة.'
                ]);
            }
            $sqlQuery     = null;
            $propertyData = [];

            if (preg_match('/```sql\n(.+?)\n```/s', $botResponse, $queryMatch)) {
                $sqlQuery    = trim($queryMatch[1]);
                $botResponse = str_replace($queryMatch[0], '', $botResponse);
                Log::info($sqlQuery);
                try {
                    $propertyData = DB::select($sqlQuery);
                    return response()->json([
                        'reply' => count($propertyData) > 0 ? $propertyData : 'لم يتم العثور على وحدات مطابقة لطلبك، يرجى تعديل الطلب.',
                    ]);
                } catch (\Exception $e) {
                    Log::info($e->getMessage());

                    return response()->json([
                        'reply' => 'لم أتمكن من فهم طلبك بشكل صحيح. من فضلك وضّح الموقع أو السعر أو عدد الغرف المطلوبة.'
                    ]);
                }
            }


        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred while processing your request. Please try again later.'
            ], 500);
        }


    }


}

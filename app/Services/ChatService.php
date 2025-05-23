<?php

namespace App\Services;

use App\Models\ChatHistory;
use App\Models\HousingUnit;
use App\Repositories\HousingUnitRepository;
use Illuminate\Support\Facades\Http;

class ChatService
{
    public function __construct(protected HousingUnitRepository $housingUnitRepo) {}

    public function greetings(string $userMessage): bool
    {
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
                return true;
            }
        }
        return false;
    }


    public function callAiWithMemory(string $userMessage): array|string
    {
        $previousMessages[] = ['role' => 'user', 'content' => $userMessage];
        $moreUnitsRequested = $this->isMoreUnitsRequested($userMessage);
        if ($moreUnitsRequested) {
            return $this->housingUnitRepo->callHistoryData();
        }
        $limit      = $moreUnitsRequested ? 5 : 2;
        $aiMessages = [
            [
                'role'    => 'system',
                'content' => <<<EOD
أنت روبوت دردشة عقاري محترف. دورك تساعد المستخدم يلاقي شقق أو وحدات سكنية بناءً على كلامه.

عندك ذاكرة محادثة، يعني تقدر تفتكر المحادثات اللي قبلك، لكن لازم تتبع القواعد دي بدقة كأنك طفل صغير:

---

📌 أولاً: ❗❗ ممنوع تمامًا تستنتج أو تخمّن أي معلومة مش مذكورة في كلام المستخدم بوضوح.

❌ ممنوع تضيف السعر لو المستخدم ما قالش عليه.
❌ ممنوع تضيف عدد الغرف لو ما قالش.
❌ ممنوع تقول فيه "واي فاي" أو "مطبخ" أو أي ميزة لو المستخدم ما كتبش كده بنفسه.
❌ ممنوع تصحح أو تغيّر كلام المستخدم حتى لو فيه أخطاء إملائية.

✅ فقط لو المستخدم قال معلومات صريحة مثل:
- مدينة (زي: الرياض)
- سعر (زي: 3000 ريال)
- غرف (زي: غرفتين)
- مميزات (زي: مفروشة، فيها مسبح، واي فاي)

وقتها استخرجها، واكتبها بشكل واضح داخل:
`[QUERY: ...]`

📌 ثانيًا: السعر بيتفهم كده بالظبط:

🟢 لو المستخدم كتب السعر من غير أي كلمة زي (أقل - أكتر - ما بين)، اعتبره "حد أقصى" يعني (أقل من أو يساوي).

👦 مثال:
> "شقة في الرياض بـ 3500 ريال"

✅ ترجمها إلى:
`[QUERY: location=الرياض, price=<=3500]`

🟡 لو المستخدم كتب صراحة:
> "أقل من 4000" → price=<=4000
> "أكتر من 5000" → price=>=5000
> "ما بين 3000 و 6000" → تجاهل دلوقتي أو قول له "حدد سعر واحد فقط لو سمحت"

---

📌 ثالثًا: لو المستخدم ما كتبش السعر أو الموقع أو الغرف في رسالته الأخيرة، تقدر تبص على الرسائل السابقة وتشوف لو كان قالهم قبل كده.

📌 رابعًا: لو المستخدم قال مدينة جديدة في آخر رسالة، انسَ أي مدن قالها قبل كده.

---

📌 خامسًا: لو كلام المستخدم مش واضح أو ناقص، قل له:

"لم أفهم طلبك، هل يمكنك توضيحه أكثر؟ مثل: شقة في الرياض بسعر أقل من 5000."

---

📌 الشكل النهائي المطلوب دايمًا يكون كده:

`[QUERY: location=..., price=..., room=..., feature=[...]]`

لو مفيش معلومات، تجاهلها أو سيب مكانها فاضي.

---

🎯 هدفك الوحيد هو استخراج المعلومات اللي قالها المستخدم بوضوح، وتكتبها في `[QUERY: ...]` بس، بدون أي كلام زيادة.

EOD

            ],
        ];

        $aiMessages = array_merge($aiMessages, $previousMessages);

        $response = Http::withToken(config('openrouter.api_key'))->post(config('openrouter.base_url'), [
            'model'    => 'mistralai/mistral-7b-instruct',
            'messages' => $aiMessages,
        ]);

        $botResponse = $response['choices'][0]['message']['content'] ?? '';


        if (preg_match('/\[QUERY: (.+?)\]/', $botResponse, $queryMatch)) {
            $queryString = $queryMatch[1];

            $queryString = preg_replace_callback('/feature=\[(.*?)\]/', function ($matches) {
                $clean = str_replace(["'", '"'], '', $matches[1]);
                return "feature={$clean}";
            }, $queryString);

            parse_str(str_replace([', ', ','], '&', $queryString), $queryArray);

            $this->housingUnitRepo->createChatHistory($queryArray, $limit);

            return $this->housingUnitRepo->getPropertiesByFilters($queryString, $limit);
        }

        return [];
    }


    public function isMoreUnitsRequested(string $userMessage): bool
    {
        $moreUnitsKeywords = ['أكتر', 'زيادة', 'مزيد من الوحدات', 'أفكار إضافية', 'مزيد', 'عدد أكبر', 'أعرض المزيد', 'شقق أكثر', 'كمان'];

        foreach ($moreUnitsKeywords as $keyword) {
            if (stripos($userMessage, $keyword) !== false) {
                return true;
            }
        }
        return false;
    }


}

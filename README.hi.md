# Aurelia: WooCommerce ब्लॉक थीम + Aurelia Commerce प्लगइन

[English](README.md) · **हिन्दी**

Aurelia किसी भी तरह की दुकान के लिए एक WooCommerce ब्लॉक थीम है: ज्वेलरी, फ़ैशन, इलेक्ट्रॉनिक्स, ब्यूटी, ग्रोसरी या होम। **Aurelia Commerce** इसका साथी प्लगइन है, जो स्टोर के फ़ीचर जोड़ता है: WhatsApp ऑर्डर, UPI QR पेमेंट, AI शॉपिंग कंसीयर्ज, 3D व्यूअर, वीडियो स्टूडियो, सोशल पोस्टिंग, एनालिटिक्स और SEO।

थीम सिर्फ़ डिज़ाइन सँभालती है, इसलिए यह प्लगइन के बिना भी काम करती है। प्लगइन दूसरी ब्लॉक थीम के साथ भी चलता है।

| ज़रूरत | वर्शन |
|---|---|
| WordPress | 6.6 या नया (7.1 तक टेस्ट किया गया) |
| PHP | 8.1 या नया |
| WooCommerce | 9.0 या नया (11.1 तक टेस्ट किया गया), HPOS और Cart/Checkout ब्लॉक सपोर्टेड |

**[▶ लाइव डेमो आज़माएँ](https://playground.wordpress.net/?blueprint-url=https://raw.githubusercontent.com/calculatewellhub-cell/e-commerce-theme/claude/charming-archimedes-euq210/playground/blueprint.json)**: WordPress Playground के ज़रिए आपके ब्राउज़र में चलने वाला पूरा डेमो स्टोर, बिना होस्टिंग और बिना साइन-अप। लोड होने में लगभग एक मिनट लगता है, और टैब बंद करने पर सब रीसेट हो जाता है।

![डेस्कटॉप पर होम पेज](docs/screenshots/home-desktop.webp)

<p>
<img src="docs/screenshots/home-phone.webp" width="24%" alt="फ़ोन पर होम पेज">
<img src="docs/screenshots/shop-phone.webp" width="24%" alt="फ़ोन पर शॉप">
<img src="docs/screenshots/filters-phone.webp" width="24%" alt="फ़ोन पर फ़िल्टर">
<img src="docs/screenshots/product-phone.webp" width="24%" alt="फ़ोन पर प्रोडक्ट पेज">
</p>

## सूची

1. [फ़ीचर](#फ़ीचर)
2. [इंस्टॉल करना](#इंस्टॉल-करना)
3. [सेटअप विज़ार्ड](#सेटअप-विज़ार्ड)
4. [WhatsApp नंबर और WhatsApp ऑर्डर](#whatsapp-नंबर-और-whatsapp-ऑर्डर)
5. [पेमेंट: UPI QR, COD और गेटवे](#पेमेंट-upi-qr-cod-और-गेटवे)
6. [Claude API key (AI कंसीयर्ज)](#claude-api-key-ai-कंसीयर्ज)
7. [सोशल पोस्टिंग: वेबहुक और Meta सेटअप](#सोशल-पोस्टिंग-वेबहुक-और-meta-सेटअप)
8. [असली क्रॉन](#असली-क्रॉन)
9. [स्टाइल वेरिएशन बदलना](#स्टाइल-वेरिएशन-बदलना)
10. [डेमो कंटेंट](#डेमो-कंटेंट)
11. [एनालिटिक्स, SEO और प्राइवेसी](#एनालिटिक्स-seo-और-प्राइवेसी)
12. [भाषाएँ](#भाषाएँ)
13. [डेवलपमेंट](#डेवलपमेंट)
14. [डिफ़ॉल्ट सेटिंग](#डिफ़ॉल्ट-सेटिंग)

## फ़ीचर

**थीम (`aurelia`)**
- सात स्टाइल वेरिएशन: Luxe (एमरल्ड और गोल्ड, डिफ़ॉल्ट), Noir, Fashion, Electronics, Beauty, Grocery और Minimal। हर रंग, फ़ॉन्ट, साइज़, रेडियस और शैडो `theme.json` से आता है।
- लाइट और डार्क मोड। मोड विज़िटर के डिवाइस के हिसाब से अपने-आप चुना जाता है, और हेडर से बदला जा सकता है। थीम RTL सपोर्ट करती है, अनुवाद के लिए तैयार है और हिन्दी अनुवाद साथ आता है।
- स्टोर टेम्पलेट: शॉप, कैटेगरी, टैग, एट्रिब्यूट, प्रोडक्ट सर्च, प्रोडक्ट, कार्ट, चेकआउट और ऑर्डर कन्फ़र्मेशन। ब्लॉग टेम्पलेट: होम, आर्काइव, सर्च, सिंगल, पेज के वेरिएंट और 404।
- हेडर: स्टिकी, जिसमें मेगा मेन्यू, प्रोडक्ट सर्च, अकाउंट, विशलिस्ट, डार्क मोड बटन और मिनी-कार्ट है।
- 35 से ज़्यादा पैटर्न:
  - हीरो: 3D, इमेज और स्प्लिट।
  - शॉप सेक्शन: कैटेगरी टाइल, प्रोडक्ट कैरोसेल, प्रोमो बैनर, काउंटडाउन सेल और भरोसा बैज।
  - भरोसा और कंटेंट: टेस्टिमोनियल, FAQ, ब्रांड लोगो, गैलरी, न्यूज़लेटर और WhatsApp के चरण।
  - पूरे पेज: होम, हमारे बारे में और संपर्क।
- फ़िल्टर और ग्रिड WooCommerce के अपने ब्लॉक (Product Collection और इंटरैक्टिव Product Filters) इस्तेमाल करते हैं, इसलिए WooCommerce अपडेट होने पर भी काम करते रहते हैं।
- सुलभता: axe से WCAG 2.2 AA के लिए टेस्ट की गई, साफ़ दिखने वाला फ़ोकस, कम मोशन का सपोर्ट और कम से कम 24px के टच टारगेट। फ़ॉन्ट सर्वर पर ही होस्ट और प्रीलोड होते हैं, और फ़्रंट-एंड पर jQuery नहीं है।

**प्लगइन (`aurelia-commerce`)**
- **WhatsApp:**
  - प्रोडक्ट पेज, कार्ट और साइड कार्ट से "WhatsApp पर ऑर्डर करें", दाम सर्वर पर जाँचे जाते हैं।
  - चाहें तो पेमेंट लिंक के साथ WooCommerce ऑर्डर ("Pending – WhatsApp") भी बनता है।
  - पूछताछ और वीडियो कॉल बटन, और एक फ़्लोटिंग चैट बटन।
- **UPI QR पेमेंट** Checkout ब्लॉक में, कैश ऑन डिलीवरी या किसी भी गेटवे प्लगइन के साथ।
- **AI कंसीयर्ज (Claude):** आपके लाइव कैटलॉग से जवाब देता है। key सर्वर पर ही रहती है और हर विज़िटर के अनुरोध सीमित रहते हैं। key न हो तो नियमों पर आधारित असिस्टेंट जवाब देता है।
- **3D व्यूअर:** बिल्ट-इन अंगूठी मॉडल या आपकी अपनी GLB/GLTF फ़ाइल। three.js तभी लोड होता है जब ग्राहक व्यूअर खोलता है।
- **इमेज → वीडियो स्टूडियो:** प्रोडक्ट फ़ोटो से ब्राउज़र में रील, शॉर्ट और फ़ीड वीडियो बनाता है और मीडिया लाइब्रेरी में सहेजता है।
- **सोशल पोस्टिंग:** Facebook और Instagram पर, या Make, Zapier या n8n के ज़रिए किसी भी नेटवर्क पर पोस्ट शेड्यूल करें। हर प्लैटफ़ॉर्म को अपना ट्रैक किया गया छोटा लिंक मिलता है।
- **एनालिटिक्स:** बिना कुकी और गुमनाम, आपके अपने डेटाबेस में।
- **SEO और AI सर्च:**
  - स्ट्रक्चर्ड डेटा, जो Yoast, Rank Math या AIOSEO को दोहराने की जगह उन्हें आगे बढ़ाता है।
  - `llms.txt`, robots.txt में AI क्रॉलर के नियम, और प्रोडक्ट फ़ीड।
- **शॉप फ़ीचर:**
  - लाइव सर्च, क्विक व्यू, विशलिस्ट, तुलना और स्वॉच।
  - ग्रिड/लिस्ट टॉगल, और "और लोड करें" या इनफ़िनिट स्क्रॉल।
  - स्टिकी ऐड-टू-कार्ट बार, डिलीवरी का अनुमान, साइज़ गाइड और रिव्यू में फ़ोटो।
  - हाल में देखे गए प्रोडक्ट, "अक्सर साथ ख़रीदे जाते हैं" और मुफ़्त शिपिंग प्रोग्रेस बार।
  - सेल काउंटडाउन, न्यूज़लेटर पॉपअप (डिफ़ॉल्ट रूप से बंद) और घोषणा पट्टी।

<p>
<img src="docs/screenshots/cart.webp" width="49%" alt="WhatsApp बटन और मुफ़्त शिपिंग बार वाला कार्ट">
<img src="docs/screenshots/checkout.webp" width="49%" alt="UPI के साथ ब्लॉक चेकआउट">
</p>

## इंस्टॉल करना

`aurelia.zip` और `aurelia-commerce.zip` [लेटेस्ट रिलीज़](../../releases/latest) से डाउनलोड करें, या `bash tools/build-zips.sh` से ख़ुद बनाएँ।

1. **WooCommerce** इंस्टॉल और चालू करें। इसके लिए Plugins → Add New इस्तेमाल करें, या `wp plugin install woocommerce --activate` चलाएँ।
2. **थीम:** Appearance → Themes → Add New Theme → **Upload Theme** पर जाएँ। `aurelia.zip` चुनें, फिर Install और **Activate** करें।
3. **प्लगइन:** Plugins → Add New Plugin → **Upload Plugin** पर जाएँ। `aurelia-commerce.zip` चुनें, फिर Install और **Activate** करें।
4. सेटअप विज़ार्ड अपने-आप खुल जाता है।

> अगर अपलोड पर "The link you followed has expired" दिखे, तो होस्ट की अपलोड सीमा छोटी है। `upload_max_filesize` और `post_max_size` को 16M करें, या अनज़िप किए फ़ोल्डर SFTP से `wp-content/themes/` और `wp-content/plugins/` में डालें।

## सेटअप विज़ार्ड

विज़ार्ड कभी भी **Aurelia → Setup wizard** से फिर खोल सकते हैं। हर चरण छोड़ा जा सकता है।

1. **लुक:** चुनें कि आप क्या बेचते हैं। विज़ार्ड उसी हिसाब से स्टाइल वेरिएशन लगा देता है, जैसे गैजेट शॉप के लिए Electronics।
2. **स्टोर का विवरण:** नाम, फ़ोन, पता और राज्य कोड (जैसे MH, DL, KA)। ये ईमेल, बिल, WhatsApp मैसेज और स्ट्रक्चर्ड डेटा में इस्तेमाल होते हैं।
3. **WhatsApp और पेमेंट:**
   - आपका WhatsApp नंबर और WhatsApp मोड।
   - कैश ऑन डिलीवरी।
   - QR पेमेंट के लिए UPI ID और प्राप्तकर्ता का नाम।
4. **AI और सोशल:** Claude API key और सोशल वेबहुक (दोनों वैकल्पिक)।
5. **डेमो कंटेंट:** पूरा डेमो स्टोर इम्पोर्ट करें, या छोड़ दें। देखें [डेमो कंटेंट](#डेमो-कंटेंट)।
6. **तैयार:** **मेरा स्टोर लॉन्च करें** दबाने पर WooCommerce का "Coming soon" मोड बंद होता है और ग्राहक स्टोर देख पाते हैं।

विज़ार्ड के बाद संपर्क का सैंपल विवरण बदलें। फ़ुटर और संपर्क पेज में सैंपल पता, फ़ोन (`+91 00000 00000`) और ईमेल (`hello@example.com`) होता है। फ़ुटर **Appearance → Editor → Patterns → Footer** में और संपर्क पेज **Pages** में बदलें।

## WhatsApp नंबर और WhatsApp ऑर्डर

**Aurelia → Settings → WhatsApp** पर जाएँ।

- **WhatsApp नंबर:** अंतरराष्ट्रीय फ़ॉर्मैट में सिर्फ़ अंक लिखें, बिना `+`, स्पेस या डैश के। भारत के लिए `91` और फिर 10 अंकों का मोबाइल नंबर, जैसे `919876543210`। हो सके तो WhatsApp Business नंबर इस्तेमाल करें।
- **चेकआउट मोड:**
  - **सामान्य चेकआउट के साथ दिखाएँ** (डिफ़ॉल्ट): ग्राहक ऑनलाइन भुगतान भी कर सकते हैं या WhatsApp पर ऑर्डर भी।
  - **चेकआउट की जगह WhatsApp ऑर्डर:** कार्ट सीधे WhatsApp पर भेजता है। यह कैटलॉग जैसी दुकानों के लिए अच्छा है।
- **हर WhatsApp ऑर्डर के लिए WooCommerce ऑर्डर बनाएँ:**
  - हर WhatsApp ऑर्डर **Pending (WhatsApp)** स्थिति वाला WooCommerce ऑर्डर बनता है, जिससे स्टॉक, रिपोर्ट और बिल सही रहते हैं।
  - WhatsApp मैसेज में ऑर्डर नंबर जाता है। "ऑनलाइन पेमेंट लिंक शामिल करें" चालू हो, तो ऐसा लिंक भी जाता है जिससे ग्राहक UPI या कार्ड से भुगतान कर सके।
- बटन अलग-अलग चालू या बंद कर सकते हैं: प्रोडक्ट पेज, कार्ट, पूछताछ, "वीडियो कॉल का अनुरोध", और फ़्लोटिंग बटन (नीचे दाएँ या बाएँ)।

ग्राहक के बटन दबाने पर WhatsApp खुलता है, जिसमें आइटम, विकल्प, मात्रा, दाम और कुल रक़म पहले से लिखे होते हैं। दाम सर्वर पर डेटाबेस से पढ़े जाते हैं, इसलिए मैसेज में छेड़छाड़ नहीं हो सकती।

## पेमेंट: UPI QR, COD और गेटवे

**UPI QR (बिल्ट-इन)।** WooCommerce → Settings → **Payments** → **UPI** पर जाकर ये भरें:

- **आपकी UPI ID (VPA):** जैसे `yourstore@okhdfcbank` या `9876543210@ybl`। यह PhonePe, Google Pay या Paytm में आपकी प्रोफ़ाइल में मिलती है।
- **प्राप्तकर्ता का नाम:** आमतौर पर बैंक में रजिस्टर्ड आपके बिज़नेस का नाम।
- **स्टैटिक QR इमेज URL (वैकल्पिक):** आपकी दुकान का प्रिंटेड QR, बैकअप के रूप में दिखता है।

ग्राहक के लिए यह ऐसे काम करता है:

1. ग्राहक चेकआउट पर UPI चुनकर ऑर्डर देता है।
2. धन्यवाद पेज पर **सही रक़म वाला QR कोड** और **"UPI ऐप से भुगतान करें"** बटन दिखता है। फ़ोन पर यह बटन PhonePe, Google Pay, Paytm या BHIM खोलता है।
3. भुगतान के बाद ग्राहक 12 अंकों का **UTR / UPI रेफ़रेंस** डालता है।
4. ऑर्डर **On hold** रहता है और UTR ऑर्डर नोट में जुड़ जाता है। अपने बैंक या UPI ऐप में भुगतान जाँचकर ऑर्डर को **Processing** करें।

**कैश ऑन डिलीवरी** WooCommerce का सामान्य COD तरीक़ा है। विज़ार्ड इसे चालू कर सकता है।

**अपने-आप पुष्टि और कार्ड:** Plugins → Add New से Razorpay, PhonePe PG, Cashfree, PayU या Paytm जैसा गेटवे प्लगइन इंस्टॉल करें। ये Checkout ब्लॉक में UPI और COD के साथ दिखते हैं, और थीम इन्हें अपने-आप स्टाइल करती है।

## Claude API key (AI कंसीयर्ज)

1. [console.anthropic.com](https://console.anthropic.com) पर अकाउंट बनाएँ, बिलिंग जोड़ें, और Settings → API keys में **API key** बनाएँ।
2. **Aurelia → Settings → AI concierge** पर जाएँ, key को **Claude API key** में चिपकाएँ और **Test connection** दबाएँ।
3. वैकल्पिक सेटिंग:
   - असिस्टेंट का नाम, स्वागत संदेश, सुझाए गए सवाल और अतिरिक्त निर्देश (टोन, किन विषयों से बचना है)।
   - शिपिंग और रिटर्न पॉलिसी का टेक्स्ट।
   - FAQ। आपके पेजों के FAQ ब्लॉक अपने-आप जुड़ जाते हैं।
   - हर विज़िटर के लिए 10 मिनट में मैसेज की सीमा।

key को डेटाबेस से बाहर रखने के लिए इसे `wp-config.php` में रखें। तब फ़ील्ड में "wp-config.php में सेट है" दिखता है:

```php
define( 'AURELIA_ANTHROPIC_API_KEY', 'sk-ant-...' );
```

सुरक्षा और ख़र्च:

- key सिर्फ़ आपके सर्वर पर `wp_remote_post` से इस्तेमाल होती है और कभी ब्राउज़र तक नहीं पहुँचती।
- हर विज़िटर के अनुरोध सीमित रहते हैं। इसके लिए रोज़ बदलने वाले salt के साथ हैश किया गया IP इस्तेमाल होता है, इसलिए कोई IP पता सहेजा नहीं जाता।
- जवाब छोटे रखे जाते हैं। कैटलॉग का संदर्भ 12 घंटे कैश रहता है, और प्रॉम्प्ट Claude के prompt caching के साथ भेजा जाता है।

डिफ़ॉल्ट मॉडल `claude-opus-5` है। **Model** में दूसरी मॉडल ID डाल सकते हैं, जैसे कम ख़र्च के लिए `claude-sonnet-5`। API न मिले या मॉडल अनुरोध मना कर दे, तो ग्राहक को विनम्र जवाब और WhatsApp पर बात करने का विकल्प मिलता है। key बिल्कुल न हो, तो नियमों पर आधारित असिस्टेंट शब्दों और बजट के हिसाब से आपका कैटलॉग खोजता है।

## सोशल पोस्टिंग: वेबहुक और Meta सेटअप

पोस्ट **Aurelia → Social posts** में बनती हैं:

1. प्रोडक्ट चुनें।
2. **✨ AI से लिखें** से कैप्शन लिखवाएँ।
3. प्लैटफ़ॉर्म चुनें, फिर शेड्यूल करें या अभी प्रकाशित करें।

हर प्लैटफ़ॉर्म को अपना ट्रैक किया गया लिंक मिलता है: `https://yourshop.com/go/<post-id>/<platform>/`। यह UTM टैग के साथ रीडायरेक्ट करता है और क्लिक डैशबोर्ड पर गिनता है।

![सोशल पोस्ट](docs/screenshots/admin-social.webp)

प्रकाशित करने के दो तरीक़े हैं। **वेबहुक सेट हो तो सभी प्लैटफ़ॉर्म के लिए वही इस्तेमाल होता है।**

### तरीक़ा A: वेबहुक (Make, Zapier, n8n)

यह हर नेटवर्क के लिए काम करता है: Instagram, Facebook, Pinterest, X, LinkedIn, WhatsApp Channel और YouTube।

1. Make, Zapier या n8n में **Custom webhook** ट्रिगर से एक सिनेरियो बनाएँ और उसका URL कॉपी करें।
2. URL को **Aurelia → Settings → Social posting → Publishing webhook** में चिपकाएँ।
3. हर पोस्ट JSON के रूप में भेजी जाती है:

```json
{
  "id": 123,
  "brand": "Your Store",
  "mediaUrl": "https://yourshop.com/wp-content/uploads/reel.mp4",
  "mediaType": "video",
  "link": "https://yourshop.com/product/linen-wrap-dress/",
  "posts": [
    { "platform": "instagram", "caption": "✨ मिलिए…", "trackedLink": "https://yourshop.com/go/123/instagram/" },
    { "platform": "pinterest", "caption": "…", "trackedLink": "https://yourshop.com/go/123/pinterest/" }
  ]
}
```

4. सिनेरियो में `posts[].platform` पर राउटर लगाएँ और हर शाखा को सही मॉड्यूल से जोड़ें, जैसे Instagram for Business, Pinterest या LinkedIn। कोई भी 2xx स्टेटस लौटाने पर पोस्ट प्रकाशित मानी जाती है।

### तरीक़ा B: सीधे Meta Graph API (Facebook Page + Instagram)

आपको एक Facebook **Page** चाहिए, और उससे जुड़ा Instagram **Professional** (Business या Creator) अकाउंट।

1. [developers.facebook.com](https://developers.facebook.com) पर **Business** टाइप का ऐप बनाएँ, फिर **Facebook Login for Business** और **Instagram Graph API** प्रोडक्ट जोड़ें।
2. **Graph API Explorer** खोलें, अपना ऐप चुनें और इन अनुमतियों के साथ **User token** बनाएँ:
   - `pages_show_list`
   - `pages_read_engagement`
   - `pages_manage_posts`
   - `instagram_basic`
   - `instagram_content_publish`
3. Access Token Debugger के "Extend access token" से इसे **लंबी अवधि** वाले टोकन में बदलें। फिर `GET /me/accounts` चलाएँ। आपके Page के साथ दिया `access_token` एक **Page token** है, जो एक्सपायर नहीं होता।
4. अपनी Instagram Business अकाउंट ID `GET /<page-id>?fields=instagram_business_account` से पता करें।
5. **Aurelia → Settings → Social posting** पर जाकर **Facebook Page ID**, **Instagram Business account ID** और **Meta Page access token** भरें। वेबहुक ख़ाली छोड़ें।

ध्यान दें:

- Instagram को इमेज या वीडियो किसी **पब्लिक HTTPS URL** पर चाहिए, इसलिए लोकल या पासवर्ड वाली साइट से काम नहीं चलेगा।
- रील पीछे-पीछे अपलोड होती हैं। प्लगइन अगले क्रॉन रन में उनकी प्रोसेसिंग जाँचता है।
- टोकन `wp-config.php` में भी रख सकते हैं: `define( 'AURELIA_META_TOKEN', '...' );`

## असली क्रॉन

WordPress का बिल्ट-इन शेड्यूलर (WP-Cron) तभी चलता है जब कोई साइट पर आता है। ऐसे में कम ट्रैफ़िक वाली साइट पर शेड्यूल की गई पोस्ट, Instagram प्रोसेसिंग और एनालिटिक्स की सफ़ाई देर से होती है। इसकी जगह असली क्रॉन जॉब इस्तेमाल करें:

1. यह पंक्ति `wp-config.php` में "That's all, stop editing!" के ऊपर जोड़ें:

   ```php
   define( 'DISABLE_WP_CRON', true );
   ```

2. हर 5 मिनट पर चलने वाला क्रॉन जॉब जोड़ें। cPanel में **Cron Jobs** इस्तेमाल करें, VPS पर `crontab -e` चलाएँ।

   ```cron
   */5 * * * * curl -s https://yourshop.com/wp-cron.php?doing_wp_cron > /dev/null 2>&1
   ```

   या, अगर WP-CLI इंस्टॉल है:

   ```cron
   */5 * * * * wp cron event run --due-now --path=/var/www/yourshop > /dev/null 2>&1
   ```

आपकी साइट के लिए सही कमांड **Aurelia → Settings → Social posting** में दिखती हैं। प्लगइन सोशल पब्लिशिंग के लिए "हर पाँच मिनट (Aurelia)" शेड्यूल, और एनालिटिक्स रिटेंशन के लिए रोज़ का एक जॉब रजिस्टर करता है।

## स्टाइल वेरिएशन बदलना

**Appearance → Editor → Styles → Browse styles** पर जाकर वेरिएशन चुनें:

| वेरिएशन | रंग | फ़ॉन्ट |
|---|---|---|
| Luxe (डिफ़ॉल्ट) | एमरल्ड और गोल्ड | Cormorant Garamond / Jost |
| Noir | हमेशा डार्क मोड वाला Luxe | Cormorant Garamond / Jost |
| Fashion | मोनोक्रोम और ब्लश | Bodoni Moda / Manrope |
| Electronics | इलेक्ट्रिक ब्लू | Space Grotesk / Inter |
| Beauty | प्लम और रोज़ | Fraunces / DM Sans |
| Grocery | ताज़ा हरा | Outfit / Nunito Sans |
| Minimal | काला और सफ़ेद | Inter |

**Save** दबाएँ। पूरा स्टोर बदल जाता है, जिसमें पैटर्न, बटन, प्रोडक्ट कार्ड, चेकआउट और डार्क मोड शामिल हैं। इसी पैनल में रंग और फ़ॉन्ट बारीकी से बदल सकते हैं, या विज़ार्ड का **लुक** चरण फिर चला सकते हैं।

सेक्शन स्टाइल (Dark section, Primary section, Soft section, Card) और ब्लॉक स्टाइल (Accent बटन, Eyebrow, Arch और Rounded इमेज) ब्लॉक साइडबार में **Styles** के तहत मिलते हैं।

## डेमो कंटेंट

विज़ार्ड का **डेमो कंटेंट** चरण, या `wp aurelia demo import`, यह सब जोड़ता है:

- छह कैटेगरी में 26 प्रोडक्ट, इमेज, रंग और साइज़ वेरिएशन, स्वॉच, 3D अंगूठियों और रिव्यू के साथ।
- कूपन `TODAY15` और `WELCOME10`।
- पेज: होम, हमारे बारे में, संपर्क, FAQ, शिपिंग और रिटर्न, विशलिस्ट, तुलना, ऑर्डर ट्रैक और जर्नल।
- मेगा मेन्यू वाला मुख्य मेन्यू।

इम्पोर्ट हुई हर चीज़ ट्रैक होती है, इसलिए उसे साफ़-साफ़ हटाया जा सकता है। **Aurelia → Setup wizard → Demo content → Remove demo content**, या `wp aurelia demo remove`, सिर्फ़ डेमो चीज़ें हटाता है। आपके अपने प्रोडक्ट और पेज कभी नहीं छुए जाते।

## एनालिटिक्स, SEO और प्राइवेसी

- **डैशबोर्ड** (Aurelia → Dashboard):
  - पेज और प्रोडक्ट व्यू, कार्ट दर, और WhatsApp ऑर्डर व उनकी वैल्यू।
  - कंसीयर्ज चैट, प्लैटफ़ॉर्म और पोस्ट के हिसाब से सोशल क्लिक, ट्रैफ़िक के स्रोत और डिवाइस।
  - हर चार्ट के साथ टेबल व्यू।
  - डेटा `wp_aurelia_events` में रहता है, बिना कुकी, IP पते या निजी डेटा के। डेटा कितने दिन रखना है, यह Settings → Analytics & SEO में तय होता है।
- **स्ट्रक्चर्ड डेटा:** Product (ऑफ़र, रिव्यू और शिपिंग या रिटर्न पॉलिसी के साथ), Store/LocalBusiness, सर्च वाली WebSite, ब्रेडक्रंब और FAQPage।
  - Yoast, Rank Math या AIOSEO चालू हो, तो उनका ग्राफ़ दोहराया नहीं जाता, आगे बढ़ाया जाता है।
  - इनमें से कोई प्लगइन चालू हो, तो Open Graph टैग छोड़ दिए जाते हैं।
- **AI सर्च (AIO):**
  - `/llms.txt` और `/llms-full.txt` AI असिस्टेंट के लिए आपके स्टोर का सार देते हैं।
  - robots.txt AI क्रॉलर को अनुमति देता है (बदला जा सकता है)।
  - `/product-feed.xml` Google Merchant और Meta कैटलॉग फ़ीड है।
- **प्राइवेसी:** प्राइवेसी पॉलिसी का सुझाया गया टेक्स्ट Settings → Privacy में मिलता है, और न्यूज़लेटर ईमेल WordPress के निजी डेटा एक्सपोर्ट और मिटाने वाले टूल में शामिल हैं।

![एनालिटिक्स डैशबोर्ड](docs/screenshots/admin-analytics.webp)

## भाषाएँ

- थीम और प्लगइन पूरी तरह अनुवाद योग्य हैं। टेम्पलेट अनुवाद योग्य पैटर्न इस्तेमाल करते हैं, इसलिए HTML में कोई टेक्स्ट पक्का लिखा नहीं है।
- हिन्दी (`hi_IN`) अनुवाद `theme/languages/` और `plugin/languages/` में साथ आते हैं। **Settings → General → Site Language** में हिन्दी चुनने पर ये अपने-आप लोड होते हैं।
- दूसरी भाषाओं के लिए टेम्पलेट `theme/languages/aurelia.pot` और `plugin/languages/aurelia-commerce.pot` हैं। इनका अनुवाद Poedit या Loco Translate से करें।
- RTL भाषाएँ सपोर्टेड हैं। लेआउट, आइकन और कैरोसेल अपने-आप उलट जाते हैं।

![RTL लेआउट](docs/screenshots/rtl.webp)

## डेवलपमेंट

```bash
npm ci                          # esbuild, three.js, qrcode-generator (वैकल्पिक: playwright, sharp)
npm run build:js                # 3D व्यूअर और QR लाइब्रेरी को plugin/assets/js में बंडल करें
npm run build:styles            # tools/build-styles.py से theme.json और styles/*.json फिर बनाएँ
bash tools/build-zips.sh        # dist/aurelia.zip और dist/aurelia-commerce.zip

composer install                # WordPress Coding Standards
vendor/bin/phpcs                # थीम और प्लगइन की जाँच (WPCS 3, PHP 8.1+ कम्पैटिबिलिटी)
```

**अनुवाद:** POT फ़ाइलें अपडेट करने के लिए नीचे की कमांड चलाएँ। `--skip-theme-json` और हेल्पर स्क्रिप्ट की ज़रूरत तभी है जब मशीन develop.svn.wordpress.org तक न पहुँच सके।

```bash
wp i18n make-pot theme theme/languages/aurelia.pot --domain=aurelia --skip-theme-json
python3 tools/i18n-theme-json.py theme theme/languages/aurelia.pot /path/to/wordpress/wp-includes/theme-i18n.json
wp i18n make-pot plugin plugin/languages/aurelia-commerce.pot --domain=aurelia-commerce --exclude=assets/js/viewer,assets/js/vendor,assets/src
wp i18n make-mo theme/languages && wp i18n make-mo plugin/languages
```

**कंटीन्यूअस इंटीग्रेशन:**
- `.github/workflows/lint.yml` हर push और pull request पर चलता है:
  - PHPCS।
  - PHP 8.1–8.4 सिंटैक्स जाँच।
  - JSON की जाँच।
- `.github/workflows/release.yml` JS बंडल और zip फिर से बनाता है। रिलीज़ प्रकाशित करने या `v*` टैग push करने पर zip को GitHub रिलीज़ से जोड़ देता है।

**फ़ोल्डर:**

```
theme/    ब्लॉक थीम: theme.json, styles/, templates/, parts/, patterns/, assets/, languages/
plugin/   aurelia-commerce: includes/ (हर फ़ीचर की अलग क्लास), assets/, languages/
tools/    बिल्ड स्क्रिप्ट (स्टाइल, JS, डेमो आर्ट, zip, i18n)
docs/     स्क्रीनशॉट
```

## डिफ़ॉल्ट सेटिंग

नए स्टोर के लिए ये डिफ़ॉल्ट चुने गए हैं। सभी विज़ार्ड या सेटिंग में बदले जा सकते हैं।

- **स्टोर की बुनियादी सेटिंग:** मुद्रा INR (₹); स्टोर का देश भारत (महाराष्ट्र)। शिपिंग ₹79 फ़्लैट है और ₹999 से ऊपर मुफ़्त, 48 घंटे में डिस्पैच और 2–7 दिन में डिलीवरी।
- **पेमेंट:** कैश ऑन डिलीवरी और UPI QR। WhatsApp ऑर्डर सामान्य चेकआउट के **साथ** चलते हैं, और हर WhatsApp ऑर्डर पर WooCommerce ऑर्डर बनता है।
- **स्टाइल:** Luxe (एमरल्ड और गोल्ड)। डार्क मोड विज़िटर के डिवाइस के हिसाब से चलता है।
- **AI कंसीयर्ज:** मॉडल `claude-opus-5`, हर विज़िटर के लिए 10 मिनट में 20 मैसेज। key न हो तो नियमों पर आधारित असिस्टेंट।
- **शॉप:** "और लोड करें" पेजिनेशन; लाइव सर्च, क्विक व्यू, विशलिस्ट, तुलना, स्वॉच और स्टिकी ऐड-टू-कार्ट बार सब चालू।
- **मार्केटिंग:** न्यूज़लेटर पॉपअप **बंद** है। घोषणा पट्टी और भरोसा बैज चालू हैं।
- **एनालिटिक्स:** चालू, बिना कुकी, डेटा 180 दिन तक रखा जाता है। सारा स्ट्रक्चर्ड डेटा चालू है, और AI क्रॉलर को अनुमति है।

## लाइसेंस

GPL-2.0-or-later।
- **फ़ॉन्ट:** SIL Open Font License। क्रेडिट `theme/readme.txt` में हैं।
- **डेमो इमेज:** इस प्रोजेक्ट के लिए बनाई गई हैं और GPL के तहत जारी हैं।

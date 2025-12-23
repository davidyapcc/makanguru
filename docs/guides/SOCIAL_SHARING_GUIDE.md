# Social Sharing Guide: "Share Your Vibe"

## Overview

The "Share Your Vibe" feature allows you to transform MakanGuru's AI recommendations into beautiful, shareable social media cards. Each card is uniquely styled based on the persona who gave you the recommendation!

---

## Quick Start

### 1. Get a Recommendation

Ask any question to MakanGuru's AI personas:
- 👵 **Mak Cik**: "Where can I get good halal food near KLCC?"
- 💪 **Gym Bro**: "I need high protein lunch in PJ"
- 💅 **Atas Friend**: "Instagram-worthy cafe with good coffee?"

### 2. Share the Response

After receiving an AI response, you'll see a **"Share This Vibe"** button below the message.

Click it to generate your personalized social card!

### 3. Preview and Share

A beautiful modal will appear showing your card with:
- Your original question
- The AI's recommendation
- Persona-specific styling
- MakanGuru branding

Choose how to share:
- 📥 **Download** - Save the SVG file
- 🔗 **Copy Link** - Copy shareable URL to clipboard
- 💬 **WhatsApp** - Share directly to WhatsApp
- 📘 **Facebook** - Share on Facebook
- 🐦 **Twitter/X** - Tweet your recommendation
- ✈️ **Telegram** - Send via Telegram

---

## Persona Styles

Each persona has a unique card design:

### 👵 The Mak Cik
- **Colors**: Warm cream and brown tones
- **Vibe**: Nurturing, value-conscious, halal-focused
- **Perfect for**: Family-friendly recommendations

### 💪 The Gym Bro
- **Colors**: Blue and athletic tones
- **Vibe**: Energetic, protein-focused, efficient
- **Perfect for**: Health-conscious food spots

### 💅 The Atas Friend
- **Colors**: Pink and rose tones
- **Vibe**: Aesthetic, upscale, Instagram-worthy
- **Perfect for**: Trendy cafes and fine dining

---

## Card Features

### What's Included

✅ **Your Question** - Highlighted prominently
✅ **AI Recommendation** - Truncated to ~280 characters
✅ **Persona Avatar** - Visual personality indicator
✅ **MakanGuru Branding** - Subtle but recognizable
✅ **Gradient Background** - Persona-specific colors
✅ **Professional Layout** - Optimized for social media

### Technical Details

- **Format**: SVG (Scalable Vector Graphics)
- **Dimensions**: 1200×630px (perfect for social media)
- **File Size**: ~5-10KB (lightweight!)
- **Resolution**: Infinite (SVG scales perfectly)
- **Compatibility**: All modern platforms

---

## Sharing Options

### 📥 Download

- Click **"Download"** to save the SVG file
- Filename: `makanguru-recommendation.svg`
- Use it anywhere: social media, presentations, printing

### 🔗 Copy Link

- Click **"Copy Link"** to copy the direct URL
- Share the link anywhere
- Recipients see the card directly (no download needed)

### 💬 WhatsApp

- One-click sharing to WhatsApp
- Pre-filled message with card link
- Great for sharing with friends and family

### 📘 Facebook

- Share directly to your Facebook feed
- Automatic rich preview with card image
- Perfect for getting group opinions

### 🐦 Twitter/X

- Tweet your recommendation
- Card appears as visual preview
- Great for engaging your followers

### ✈️ Telegram

- Share to Telegram chats or channels
- Instant preview of the card
- Perfect for community groups

---

## Best Practices

### Getting Great Cards

1. **Ask Clear Questions**
   - ✅ "Where to get spicy food in PJ?"
   - ❌ "Food?"

2. **Use Relevant Personas**
   - Mak Cik → Family meals, halal, value
   - Gym Bro → Healthy, protein, quick
   - Atas Friend → Aesthetic, upscale, trendy

3. **Keep It Concise**
   - Questions are truncated to 100 characters
   - Recommendations to 280 characters
   - Focus on the key points

### Sharing Tips

1. **Add Context**: Write a caption when sharing
2. **Tag Friends**: Mention who should see it
3. **Use Hashtags**: #MakanGuru #MakanMana #MalaysianFood
4. **Credit**: Keep the MakanGuru branding intact
5. **Engage**: Ask followers for their opinions

---

## Privacy & Storage

### Card Lifetime

- Cards are stored for **7 days**
- Automatic cleanup after expiry
- Download if you want to keep it longer

### Privacy

- Cards contain only:
  - Your question (publicly visible)
  - AI's response (publicly visible)
  - No personal information
  - No account details
  - No location data

### Sharing Permissions

- ✅ Share anywhere you like
- ✅ Use in presentations
- ✅ Include in blogs/articles
- ✅ Print for personal use
- ❌ Don't remove MakanGuru branding
- ❌ Don't claim as your own design

---

## Mobile Experience

### Responsive Design

Cards look great on:
- 📱 Mobile phones
- 💻 Tablets
- 🖥️ Desktop computers
- 📺 Large displays

### Touch-Friendly

- Large tap targets for buttons
- Easy scrolling in modal
- Swipe-friendly interface
- Optimized for one-handed use

---

## Troubleshooting

### Card Not Generating?

**Check:**
1. You're clicking on an **AI response**, not your question
2. You haven't hit the rate limit (5 messages/minute)
3. Your browser allows JavaScript
4. You're not in incognito/private mode

**Solution:**
- Wait a few seconds and try again
- Refresh the page
- Clear browser cache

### Download Not Working?

**Check:**
1. Browser allows downloads
2. Pop-up blocker is disabled
3. Sufficient storage space

**Solution:**
- Right-click on card and "Save Image As"
- Use "Copy Link" instead and download from URL
- Try a different browser

### Share Links Don't Work?

**Check:**
1. Card hasn't expired (7 days)
2. You copied the full URL
3. Recipient's network allows MakanGuru

**Solution:**
- Generate a fresh card
- Download and share the SVG file directly
- Share a screenshot instead

### Modal Won't Close?

**Solutions:**
- Press **ESC** key
- Click outside the modal
- Refresh the page

---

## Advanced Usage

### For Developers

**Programmatic Card Generation:**
```php
use App\Services\SocialCardService;

$service = app(SocialCardService::class);

$filename = $service->generateCard(
    recommendation: 'Visit this amazing restaurant!',
    persona: 'makcik',
    userQuery: 'Where to eat?'
);

echo $service->getCardUrl($filename);
```

**Cleanup Old Cards:**
```bash
php artisan makanguru:cleanup-cards
```

**Schedule Cleanup (Kernel):**
```php
$schedule->command('makanguru:cleanup-cards')->daily();
```

---

## Examples

### Example 1: Halal Breakfast

**Question:** "Where to get halal breakfast near KLCC?"

**Persona:** The Mak Cik

**Response:** "Adoi, go to Madam Kwan's at KLCC! They have proper nasi lemak with sambal that will wake you up. Halal, not too expensive, and the portion enough to share lah!"

**Card Features:**
- Warm cream background
- Brown text tones
- Mak Cik avatar (👵)
- Nurturing vibe

---

### Example 2: Protein Meal

**Question:** "High protein lunch in PJ?"

**Persona:** The Gym Bro

**Response:** "Bro! Hit up that MyBurgerLab outlet, order the Ramly-fied. Or better yet, Kenny Rogers for that roasted chicken breast. Confirm padu protein gainz!"

**Card Features:**
- Blue athletic theme
- Energetic styling
- Gym Bro avatar (💪)
- Motivational tone

---

### Example 3: Aesthetic Cafe

**Question:** "Instagram-worthy cafe with good coffee?"

**Persona:** The Atas Friend

**Response:** "Darling, VCR has the most divine flat whites and the interior? Simply stunning. Perfect lighting for photos. Worth every penny."

**Card Features:**
- Pink elegant design
- Upscale aesthetics
- Atas Friend avatar (💅)
- Sophisticated vibe

---

## FAQ

### Q: Can I edit the card after generating?

**A:** No, cards are pre-rendered. Generate a new one with a different question/persona if needed.

### Q: How many cards can I generate?

**A:** No hard limit, but you're subject to the chat rate limit (5 messages per minute).

### Q: Can I use cards commercially?

**A:** Personal and non-commercial use only. Keep MakanGuru branding intact.

### Q: What happens to my cards after 7 days?

**A:** They're automatically deleted from our servers. Download if you want to keep them.

### Q: Can I share cards on Instagram Stories?

**A:** Yes! Download the SVG and upload to Instagram. It scales perfectly.

### Q: Why SVG instead of PNG/JPG?

**A:** SVG is scalable, lightweight, and looks perfect at any size. Plus it's web-native!

### Q: Can I customize the card design?

**A:** Not currently. Each persona has a fixed style. This ensures brand consistency.

### Q: Do cards work offline?

**A:** Yes! Once downloaded, SVG files work offline and can be shared via any method.

---

## Support

### Need Help?

- 📖 Read the [full documentation](../README.md)
- 💬 Ask in chat: "How do I share recommendations?"
- 🐛 Report bugs on GitHub
- 💡 Suggest features via issues

### Common Links

- [Main Documentation](../README.md)
- [Phase 6 Implementation](../implementation/PHASE6_COMPLETE.md)
- [Rate Limiting Guide](./RATE_LIMITING.md)
- [Scraper Guide](./SCRAPER_GUIDE.md)

---

## Credits

Designed and developed with ❤️ by the MakanGuru team.

**Powered by:**
- Laravel 12
- Livewire 3
- Tailwind CSS v4
- SVG magic

---

*Last Updated: 2025-12-23*
*Feature: Phase 6 - "Share Your Vibe"*

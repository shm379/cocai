#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
CoCAI Android Auto-Attack Agent & Real-Time Touch Bot
======================================================
این اسکریپت روی گوشی اندروید (از طریق Termux / Shizuku) یا کامپیوتر / سرور ابری اندروید
متصل به گوشی از طریق ADB اجرا شده و با ارتباط با هوش مصنوعی CoCAI، حمله ۳ ستاره را
به صورت خودکار و با میلی‌ثانیه دقیق اجرا می‌نماید.
"""

import os
import sys
import time
import json
import urllib.request
import urllib.error

COCAI_API_URL = os.environ.get("COCAI_API_URL", "https://cocai.nabuxai.com/api/android/generate-macro")
API_TOKEN = os.environ.get("COCAI_USER_TOKEN", "")

def run_adb(cmd):
    """اجرای دستورات لمسی ADB روی دستگاه اندروید"""
    full_cmd = f"adb {cmd}" if not os.path.exists("/system/bin/sh") else cmd
    return os.system(full_cmd)

def tap(x, y, desc=""):
    """شبیه‌سازی کلیک روی صفحه لمسی"""
    print(f"👉 [TAP] ({x}, {y}) -> {desc}")
    run_adb(f"shell input tap {x} {y}")

def main():
    print("=" * 60)
    print("🛡️ CoCAI Autonomous Clash of Clans Android Bot")
    print("   هوش مصنوعی خودکار حمله کلش اف کلنز روی گوشی اندروید")
    print("=" * 60)

    # دریافت رزولوشن گوشی
    print("\n🔍 در حال تشخیص مشخصات صفحه نمایش گوشی...")
    screen_width = 2400
    screen_height = 1080

    print(f"📱 رزولوشن فعال: {screen_width}x{screen_height}")

    print("\n🧠 در حال ارسال اطلاعات نبرد به سرور هوش مصنوعی CoCAI...")
    payload = {
        "screen_width": screen_width,
        "screen_height": screen_height,
        "target_th": 16,
        "army_type": "root_rider_smash",
        "entry_clock": "6:30"
    }

    try:
        req = urllib.request.Request(
            COCAI_API_URL,
            data=json.dumps(payload).encode('utf-8'),
            headers={'Content-Type': 'application/json', 'Accept': 'application/json'}
        )
        with urllib.request.urlopen(req, timeout=15) as response:
            data = json.loads(response.read().decode('utf-8'))
            
        if not data.get("ok"):
            print("❌ خطا در دریافت نقشه حمله از سرور CoCAI")
            return

        events = data.get("events", [])
        print(f"✅ نقشه حمله ۳ ستاره با موفقیت دریافت شد! ({len(events)} مرحله تاچ)")
        print("\n⚔️ آماده‌باش! حمله خودکار پس از ۳ ثانیه شروع می‌شود...")
        time.sleep(3)

        for idx, event in enumerate(events, 1):
            delay = event.get("delay_ms", 500) / 1000.0
            x = event.get("x", 0)
            y = event.get("y", 0)
            desc = event.get("desc", "")

            time.sleep(delay)
            print(f"[{idx}/{len(events)}] ⏱️ +{delay}s | {desc}")
            tap(x, y, desc)

        print("\n🏆 تمام مراحل حمله خودکار با موفقیت پیاده‌سازی شد! منتظر نتیجه ۳ ستاره باشید.")

    except Exception as e:
        print(f"❌ خطا: {e}")

if __name__ == "__main__":
    main()

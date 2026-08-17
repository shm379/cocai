# CoCAI Cloud Emulator Workers (Redroid)

این پوشه شامل پیکربندی اجرای ایمولاتورهای اندرویدی بسیار سبک برای سرور لینوکس است.
با استفاده از Redroid (اندروید داخل داکر)، سرور شما می‌تواند به طور همزمان چندین اندروید مجازی را بدون نیاز به رابط گرافیکی (Headless) اجرا کند.

## پیش‌نیاز سرور
سرور لینوکس (اوبونتو) با پشتیبانی از ماژول‌های کرنل `ashmem` و `binder` (معمولاً سرورهای Bare Metal یا VPS با مجازی‌سازی KVM).

## نصب و اجرا

۱. نصب ماژول‌های کرنل اندروید:
```bash
sudo apt install linux-modules-extra-`uname -r`
sudo modprobe binder_linux devices="binder,hwbinder,vndbinder"
sudo modprobe ashmem_linux
```

۲. راه‌اندازی ایمولاتورها:
```bash
docker-compose up -d
```

۳. اتصال پایتون (بات) به این ایمولاتور:
پورت `5555` برای اکانت اول و `5556` برای اکانت دوم باز است.
بات پایتون CoCAI به صورت زیر متصل می‌شود:
```bash
adb connect localhost:5555
```

۴. نصب کلش اف کلنز:
از طریق ADB فایل APK کلش را نصب کنید:
```bash
adb -s localhost:5555 install Clash_of_Clans.apk
```

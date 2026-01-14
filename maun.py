import requests
import time
from colorama import Fore, Style

def banner():
    print(Fore.CYAN + "===================================")
    print(Fore.YELLOW + "FORUM CALIFORNIA API YÖNETİMİ")
    print(Fore.GREEN + "Powered By Copilot")
    print(Fore.MAGENTA + "WEB ADRESS : https://viosrio.serv00.net")
    print(Fore.CYAN + "===================================" + Style.RESET_ALL)

def get_api_data(url):
    try:
        response = requests.get(url, timeout=10)  # timeout ile donma engellenir
        if response.status_code == 200:
            return response.json()
        else:
            print("API isteği başarısız:", response.status_code, flush=True)
            return None
    except Exception as e:
        print("Hata:", e, flush=True)
        return None

def send_to_telegram(token, chat_id, msg):
    try:
        telegram_url = f"https://api.telegram.org/bot{token}/sendMessage"
        payload = {"chat_id": chat_id, "text": msg}
        r = requests.post(telegram_url, data=payload, timeout=10)
        if r.status_code != 200:
            print("Telegram gönderim hatası:", r.text, flush=True)
    except Exception as e:
        print("Telegram gönderim hatası:", e, flush=True)

def save_to_txt(msg):
    with open("log.txt", "a", encoding="utf-8") as f:
        f.write(msg + "\n")

def main():
    banner()
    url = "https://viosrio.serv00.net/get/reg.php?action=bulk&count=5"

    mode = int(input("\n[1] • TELEGRAM BOTA AKTARMAK\n[2] • YEREL TXT Bölümüne Kaydet\n\nSeçiminiz: "))

    BOT_TOKEN, CHAT_ID = None, None
    if mode == 1:
        BOT_TOKEN = input("Telegram Token: ")
        CHAT_ID = input("Chat ID: ")

    # Sonsuz döngü yerine sınırlı tekrar (örneğin 10 kez)
    for i in range(10):
        data = get_api_data(url)
        if data:
            for user in data.get("users", []):
                username = user.get("username")
                email = user.get("email")
                password = user.get("password")

                msg = f"✅ BAŞARIYLA OLDU\n\nKULLANICI AD : {username} |\n EMAİL : {email} |\n ŞİFRE : {password}"
                print(msg, flush=True)  # çıktıyı anında göster

                if mode == 1 and BOT_TOKEN and CHAT_ID:
                    send_to_telegram(BOT_TOKEN, CHAT_ID, msg)
                elif mode == 2:
                    save_to_txt(msg)

        time.sleep(5)

if __name__ == "__main__":
    main()

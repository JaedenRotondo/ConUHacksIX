# ConUHacksIX - ResQLens

## Description
**ResQLens** is an AI-powered emergency response application designed for **Metaglass**. It enables users to quickly and efficiently contact emergency services (e.g., 911) using voice commands or images captured through Metaglass.

In life-threatening situations, calling emergency services using a phone can be slow and stressful. **ResQLens** eliminates these hurdles by leveraging **AI (such as ChatGPT)** to summarize and communicate emergency details concisely to dispatchers, ensuring **faster response times**.

## Features
- **Hands-Free Emergency Calling** – Users can trigger an emergency call just by speaking to Metaglass or taking a picture.
- **AI-Powered Summarization** – ChatGPT processes user input and formulates clear, concise emergency reports.
- **WhatsApp Integration** – Uses WhatsApp to relay emergency messages since **Meta has not released an SDK** for Metaglass.
- **Automated Emergency Calls** – The system connects users to emergency services on their behalf.

## Installation & Setup
### Requirements
- A **Metaglass** device.
- A **WhatsApp Business Account** to send emergency messages.
- A **PHP Server** (hosted on GitHub) to handle message processing and AI communication.

### Setup Process
1. **Clone the Repository**
   ```bash
   git clone <repo-link>
   cd ConUHacksIX
   ```
2. **Install PHP Dependencies**
   ```bash
   composer install
   ```
3. **Configure WhatsApp Business API**
   - Set up a WhatsApp Business account.
   - Link it to the PHP server so it can receive emergency messages.
4. **Run the Server**
   ```bash
   php -S localhost:8000
   ```

## Usage
1. Wear your **Metaglass**.
2. Speak a command (e.g., *"Help, I'm injured"*) or take a picture of the scene.
3. The message is sent via **WhatsApp** to our PHP server.
4. The server processes the message using AI, summarizes the emergency, and initiates a **911 call** on your behalf.

## Technologies Used
- **PHP** – Backend server for handling WhatsApp requests.
- **WhatsApp API** – Facilitates message relays.
- **ChatGPT** – Processes and summarizes emergency descriptions.
- **Metaglass** – Hardware used to capture user input.

## Contributors
This project was developed at **ConUHacksIX** by:
- **Taha Khoumani**
- **Mounir Aiache**
- **Ahmed Saad**
- **Jaeden Rotondo**

## License
*(Specify the license type, e.g., MIT, Apache, or GNU. If unsure, use MIT.)*

## Acknowledgments
Special thanks to **ConUHacksIX** for the opportunity to develop this innovative solution.
The php server implementation was greately inspired by [Link text](https://jovanovski.medium.com/part-2-getting-chatgpt-working-on-meta-smart-glasses-82e74c9a6e1e)

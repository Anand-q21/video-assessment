# 🚀 Free Deployment Guide: Render + TiDB Cloud

This guide will help you deploy your PHP application for **FREE** using:
- **Render** for the application hosting.
- **TiDB Cloud** for the free MySQL database.

---

## Part 1: Set Up Free MySQL Database (TiDB Cloud)

1.  **Sign Up:** Go to [TiDB Cloud](https://tidbcloud.com/) and sign up for a free account.
2.  **Create Cluster:**
    -   Click **"Create Cluster"**.
    -   Select **"Serverless"** (Free Forever).
    -   Choose a region close to you (e.g., Singapore or Mumbai if available).
    -   Click **"Create"**.
3.  **Get Connection Info:**
    -   Once created, click **"Connect"**.
    -   Select **"PDO"** or **"General"** tab.
    -   **Copy the Connection String.** It will look like this:
        ```
        mysql://username:password@gateway01....tidbcloud.com:4000/test?ssl={"ca":"/etc/ssl/certs/ca-certificates.crt"}
        ```
    -   **IMPORTANT:** You MUST add `?ssl={"ca":"/etc/ssl/certs/ca-certificates.crt"}` to the end if it's not there.
    -   **Save this URL.** You will need it for Render.

---

## Part 2: Deploy App on Render

1.  **Sign Up:** Go to [Render](https://render.com/) and sign up.
2.  **New Web Service:**
    -   Click **"New +"** -> **"Web Service"**.
    -   Connect your GitHub repository.
3.  **Configure:**
    -   **Name:** `video-platform-api`
    -   **Region:** Singapore (or closest to you).
    -   **Branch:** `main`
    -   **Runtime:** `Docker` (IMPORTANT!)
    -   **Instance Type:** `Free`
4.  **Environment Variables:**
    -   Scroll down to **"Environment Variables"**.
    -   Click **"Add Environment Variable"**.
    -   **Key:** `DATABASE_URL`
    -   **Value:** Paste the TiDB Connection String you saved earlier.
5.  **Deploy:**
    -   Click **"Create Web Service"**.

---

## Part 3: Verification

1.  Wait for the deployment to finish (it might take 5-10 minutes).
2.  Click the URL provided by Render (e.g., `https://video-platform-api.onrender.com`).
3.  Add `/health.php` to the end to test:
    -   `https://video-platform-api.onrender.com/health.php`
4.  If you see `{"status":"ok"...}`, you are live! 🚀

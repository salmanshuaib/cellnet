package com.text.retriever;

import android.content.BroadcastReceiver;
import android.content.Context;
import android.content.Intent;
import android.os.Build;
import android.os.Bundle;
import android.telephony.SmsMessage;
import android.util.Log;
import okhttp3.MediaType;
import okhttp3.OkHttpClient;
import okhttp3.Request;
import okhttp3.RequestBody;
import okhttp3.Response;
import org.json.JSONObject;

import java.util.concurrent.TimeUnit;

public class SMSReceiver extends BroadcastReceiver {
    private static final String TAG = "SMSReceiver";
    private OkHttpClient client;
    private static final String WEBHOOK_URL = "https://FlowerEconomics.com/wp-json/my-webhooks/v1/webhook/text";  // replace this with your actual URL
    private static final MediaType JSON = MediaType.get("application/json; charset=utf-8");

    public SMSReceiver() {
        this.client = new OkHttpClient.Builder()
                .connectTimeout(20, TimeUnit.SECONDS)
                .readTimeout(20, TimeUnit.SECONDS)
                .build();
    }

    @Override
    public void onReceive(Context context, Intent intent) {
        if ("android.provider.Telephony.SMS_RECEIVED".equals(intent.getAction())) {
            Bundle extras = intent.getExtras();
            if (extras != null) {
                Object[] pdus = (Object[]) extras.get("pdus");
                if (pdus != null && pdus.length > 0) {
                    SmsMessage[] messages = new SmsMessage[pdus.length];
                    for (int i = 0; i < pdus.length; i++) {
                        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.M) {
                            String format = extras.getString("format");
                            messages[i] = SmsMessage.createFromPdu((byte[]) pdus[i], format);
                        } else {
                            messages[i] = SmsMessage.createFromPdu((byte[]) pdus[i]);
                        }
                    }

                    // Extract necessary information from the messages
                    String messageBody = messages[0].getMessageBody();
                    String sender = messages[0].getOriginatingAddress();

                    // Convert the message body and keyword to lowercase for case-insensitive comparison
                    String lowerCaseMessageBody = messageBody.toLowerCase();
                    String lowerCaseKeyword = "sentinel".toLowerCase();

                    if (lowerCaseMessageBody.contains(lowerCaseKeyword)) {
                        // Keyword "sentinel" (case-insensitive) found in the message
                        // Log the entire message and sender's number (second, third, and fourth digits)
                        Log.i(TAG, "SMS contained the word 'sentinel': " + messageBody);
                        if (sender != null && sender.length() > 4) {
                            Log.i(TAG, "Sender's number, digits 2-4: " + sender.substring(1, 4));
                            // Trigger the webhook
                            try {
                                JSONObject payload = new JSONObject();
                                payload.put("text", messageBody);
                                payload.put("FromNumber", sender.substring(1, 4));
                                payload.put("OccurredAt", System.currentTimeMillis());

                                RequestBody body = RequestBody.create(payload.toString(), JSON);
                                Request request = new Request.Builder()
                                        .url(WEBHOOK_URL)
                                        .post(body)
                                        .addHeader("Content-Type", "application/json")
                                        .build();

                                // Execute the request
                                Response response = client.newCall(request).execute();

                                Log.i(TAG, "Webhook response: " + response.body().string());
                            } catch (Exception e) {
                                Log.e(TAG, "Error in sending request", e);
                            }
                        } else {
                            Log.w(TAG, "Sender's number is not long enough to extract digits 2-4");
                        }
                    }
                }
            }
        }
    }
}
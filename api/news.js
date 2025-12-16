export default async function handler(req, res) {
  // Enable CORS
  res.setHeader('Access-Control-Allow-Credentials', 'true');
  res.setHeader('Access-Control-Allow-Origin', '*');
  res.setHeader('Access-Control-Allow-Methods', 'GET,OPTIONS,PATCH,DELETE,POST,PUT');
  res.setHeader('Access-Control-Allow-Headers', 'X-CSRF-Token, X-Requested-With, Accept, Accept-Version, Content-Length, Content-MD5, Content-Type, Date, X-Api-Version');

  if (req.method === 'OPTIONS') {
    res.status(200).end();
    return;
  }

  const apiKey = process.env.NEWS_API_KEY;

  if (!apiKey) {
    return res.status(500).json({
      error: 'API Key not configured',
      message: 'Please set NEWS_API_KEY in Vercel environment variables'
    });
  }

  try {
    const q = req.query.q || '';
    const country = req.query.country || 'us';

    let url;

    if (q) {
      url = `https://newsapi.org/v2/everything?q=${encodeURIComponent(q)}`;
    } else {
      url = `https://newsapi.org/v2/top-headlines?country=${encodeURIComponent(country)}`;
    }

    const response = await fetch(url, {
      headers: {
        'X-Api-Key': apiKey,
        'User-Agent': 'SimpleNewsViewer/1.0'
      }
    });

    const data = await response.json();

    if (!response.ok) {
      return res.status(response.status).json(data);
    }

    res.status(200).json(data);

  } catch (error) {
    res.status(500).json({
      error: 'Request failed',
      message: error.message
    });
  }
});

app.listen(PORT, () => {
  console.log(`✅ API berjalan di http://localhost:${PORT}`);
});

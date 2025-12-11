export default async function handler(req, res) {
  const apiKey = process.env.NEWS_API_KEY;

  if (!apiKey) {
    return res.status(500).json({
      error: 'API Key not configured',
      message: 'Please set NEWS_API_KEY in Vercel environment variables'
    });
  }

  const query = req.query.q || '';
  const country = req.query.country || 'us';

  const endpoint = 'https://newsapi.org/v2/top-headlines';
  const params = new URLSearchParams();
  
  if (query) {
    params.append('q', query);
  } else {
    params.append('country', country);
  }

  const url = `${endpoint}?${params.toString()}`;

  try {
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
}

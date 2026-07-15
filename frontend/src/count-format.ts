export interface CountMetrics {
  likes: number;
  dislikes: number;
  rating: number;
  total: number;
}

export function metricsFromCounts(counts: Record<string, number>): CountMetrics {
  const likes = (counts.like ?? 0) + (counts.up ?? 0);
  const dislikes = (counts.dislike ?? 0) + (counts.down ?? 0);
  const total = Object.values(counts).reduce((sum, value) => sum + value, 0);

  return {
    likes,
    dislikes,
    rating: likes - dislikes,
    total,
  };
}

/**
 * Mirrors Reactions\Support\CountFormat::apply (PHP).
 */
export function applyCountFormat(
  format: string,
  builtIns: Record<string, string>,
  named: Record<string, number>,
): string {
  const replacements: Record<string, string> = { ...builtIns };

  for (const [name, count] of Object.entries(named)) {
    replacements[`{${name}}`] = String(count);
  }

  const tokenRe = /\{([a-z][a-z0-9_]*)\}/g;
  let match: RegExpExecArray | null;
  while ((match = tokenRe.exec(format)) !== null) {
    const key = `{${match[1]}}`;
    if (!(key in replacements)) {
      replacements[key] = '0';
    }
  }

  const keys = Object.keys(replacements).sort((a, b) => b.length - a.length);
  let output = format;
  for (const key of keys) {
    output = output.split(key).join(replacements[key]);
  }

  return output;
}

export function renderCountText(
  format: string,
  counts: Record<string, number>,
  typeFilter?: string,
): string {
  const source = typeFilter
    ? { [typeFilter]: counts[typeFilter] ?? 0 }
    : counts;
  const metrics = metricsFromCounts(source);
  const pctUp = metrics.total > 0 ? Math.round(metrics.likes / metrics.total * 100) : 0;
  const pctDown = metrics.total > 0 ? Math.round(metrics.dislikes / metrics.total * 100) : 0;

  return applyCountFormat(
    format,
    {
      '{TOTAL}': String(metrics.total),
      '{LIKES}': String(metrics.likes),
      '{DISLIKES}': String(metrics.dislikes),
      '{RATING}': String(metrics.rating),
      '{PCT_UP}': String(pctUp),
      '{PCT_DOWN}': String(pctDown),
    },
    source,
  );
}

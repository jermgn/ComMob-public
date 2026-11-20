export interface ContextCandidate {
  id: string;
  label: string;
  score: number;
}

export interface Pictogram {
  word: string;
  pictogramId: number;
  allKeywords?: string[];
}

export interface ContextResult {
  context: string;
  keywords: Pictogram[];
  confidence: number;
}

export interface SearchResult {
  query: string;
  results: Pictogram[];
  count: number;
}
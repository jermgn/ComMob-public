import type { ContextCandidate, ContextResult, SearchResult } from "@/types/context";
import { apiFetch } from "./api";

export async function fetchIpLocation(): Promise<{ latitude: number; longitude: number } | null> {
  try {
    const res = await fetch('https://ipapi.co/json/')
    const data = await res.json()
    if (data.latitude && data.longitude) {
      return { latitude: data.latitude, longitude: data.longitude }
    }
    return null
  } catch {
    return null
  }
}

export async function fetchContexts(lat: number, lng: number): Promise<ContextCandidate[]> {
  return apiFetch<ContextCandidate[]>("/contexts", {
    method: "POST",
    body: JSON.stringify({ latitude: lat, longitude: lng })
  });
}

export async function fetchContext(contextId: string): Promise<ContextResult> {
  return apiFetch<ContextResult>(`/context/${contextId}`);
}

export async function fetchAllContexts(): Promise<ContextCandidate[]> {
  return apiFetch<ContextCandidate[]>("/all-contexts");
}

export async function searchPictograms(query: string): Promise<SearchResult> {
  return apiFetch<SearchResult>(`/search?q=${encodeURIComponent(query)}`);
}

export async function generatePhrase(words: string[]): Promise<{ phrase: string }> {
  return apiFetch<{ phrase: string }>("/generate-phrase", {
    method: "POST",
    body: JSON.stringify({ words })
  });
}

export async function generateResponses(context: string, speech: string): Promise<{ context: string; speech: string; responses: string[] }> {
  return apiFetch<{ context: string; speech: string; responses: string[] }>("/generate-responses", {
    method: "POST",
    body: JSON.stringify({ context, speech })
  });
}

export async function expandResponse(context: string, response: string): Promise<{ original: string; expanded: string }> {
  return apiFetch<{ original: string; expanded: string }>("/expand-response", {
    method: "POST",
    body: JSON.stringify({ context, response })
  });
}

export async function modifyResponse(context: string, speech: string, response: string): Promise<{ original: string; modified: string }> {
  return apiFetch<{ original: string; modified: string }>("/modify-response", {
    method: "POST",
    body: JSON.stringify({ context, speech, response })
  });
}
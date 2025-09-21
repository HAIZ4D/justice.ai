PROBLEM STATEMENT: AI Legal Assistant for the Rakyat

Legal documents are often complex, lengthy, and difficult for non-experts to interpret. Individuals and small businesses may struggle to identify important clauses or understand potential legal risks. This challenge explores how AI could be applied to make legal information more accessible, understandable, and actionable for non-lawyers.

Project Name: Justice.AI
Team Name: Sigma Coders
1. Muhammad Haizad
2. Hafiy Azfar
3. Hazriq Haykal
4. Muhammad Naim

Project Description:
Our project is an Justice AI designed to make complex legal documents easier to understand for individuals and small businesses. Legal contracts are often lengthy and filled with difficult terms, creating barriers for non-experts. Our solution addresses this by combining AI, cloud services, and open-source tools to provide clear, accessible insights with citations.
The backend is built on Laravel, deployed using AWS Elastic Beanstalk for scalable and secure management. Documents are stored in Amazon S3 with metadata in Amazon RDS, while Amazon OpenSearch Service indexes content for both keyword and semantic search. We integrate Amazon Bedrock to generate AI-driven explanations and clause summaries, ensuring responses are accurate and grounded in the original text. For accessibility, Amazon Transcribe enables voice input and Amazon Polly reads answers aloud, making the platform inclusive for visually impaired or mobile users. On the frontend, PDF.js allows users to view their documents, highlight key clauses, and directly connect AI answers to specific pages.

Key features include:
- AI-powered Q&A with grounded answers and legal clause citations.
- Searchable documents through hybrid keyword + semantic search.
- Accessibility support via speech-to-text and text-to-speech.
- Cloud-native design with secure storage, autoscaling, and regional compliance.
- This project directly addresses the challenge of legal complexity by providing an intelligent, user-friendly assistant that empowers people to understand their rights and obligations.

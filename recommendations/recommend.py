import pandas as pd
import numpy as np
from sklearn.metrics.pairwise import cosine_similarity
import json
import os

# Define paths
csv_path = os.path.join('recommendations', 'user_orders.csv')
output_path = os.path.join('recommendations', 'recommendations.json')

# Check if CSV exists
try:
    df = pd.read_csv(csv_path)
except FileNotFoundError:
    print(f"Error: {csv_path} not found. Please run export_orders_to_csv.php first.")
    exit(1)

# Check if DataFrame is empty
if df.empty:
    print(f"Error: {csv_path} is empty. No order data available.")
    with open(output_path, 'w') as f:
        json.dump({}, f)
    print(f"Empty recommendations saved to {output_path}")
    exit(1)

# Create user-item matrix
user_item_matrix = df.pivot_table(index='u_id', columns='d_id', values='quantity', fill_value=0)

# Compute item similarity
item_similarity = cosine_similarity(user_item_matrix.T)
item_similarity_df = pd.DataFrame(item_similarity, index=user_item_matrix.columns, columns=user_item_matrix.columns)

def get_recommendations(user_id, user_item_matrix, item_similarity_df, top_n=5):
    if user_id not in user_item_matrix.index:
        return []
    
    user_orders = user_item_matrix.loc[user_id]
    ordered_items = user_orders[user_orders > 0].index
    
    scores = pd.Series(0.0, index=user_item_matrix.columns)
    for item in ordered_items:
        sim_scores = item_similarity_df[item]
        scores += sim_scores * user_orders[item]
    
    scores = scores.drop(ordered_items, errors='ignore')
    top_items = scores.nlargest(top_n).index.tolist()
    return top_items

# Generate recommendations for all users
recommendations = {}
for user_id in user_item_matrix.index:
    recommendations[str(user_id)] = get_recommendations(user_id, user_item_matrix, item_similarity_df)

# Save recommendations
with open(output_path, 'w') as f:
    json.dump(recommendations, f)

print(f"Recommendations saved to {output_path}")
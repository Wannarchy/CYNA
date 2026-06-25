import React, { useState, useEffect, useRef } from 'react';
import { View, Text, TextInput, TouchableOpacity, FlatList, StyleSheet, KeyboardAvoidingView, Platform, ActivityIndicator } from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import { useNavigation } from '@react-navigation/native';
import type { NativeStackNavigationProp } from '@react-navigation/native-stack';
import { useQuery, useMutation } from '@tanstack/react-query';
import api from '../services/api';
import { RootStackParamList } from '../navigation/AppNavigator';

type ChatbotScreenNavigationProp = NativeStackNavigationProp<RootStackParamList>;

interface Message {
  id: string;
  text: string;
  sender: 'user' | 'bot';
}

export default function ChatbotScreen() {
  const navigation = useNavigation<ChatbotScreenNavigationProp>();
  const [inputText, setInputText] = useState('');
  const [messages, setMessages] = useState<Message[]>([]);
  const flatListRef = useRef<FlatList>(null);

  // 1. Charger l'historique au démarrage
  const { data: historyData, isLoading: loadingHistory } = useQuery({
    queryKey: ['chatHistory'],
    queryFn: async () => (await api.get('/chat/history')).data,
  });

  useEffect(() => {
    if (historyData) {
      const rawHistory = historyData?.data ?? historyData;
      if (Array.isArray(rawHistory) && rawHistory.length > 0) {
        const formattedMessages: Message[] = [];
        rawHistory.forEach((item: any) => {
          if (item.user_message) {
            formattedMessages.push({ id: `u-${item.id}`, text: item.user_message, sender: 'user' });
          }
          if (item.bot_response) {
            formattedMessages.push({ id: `b-${item.id}`, text: item.bot_response, sender: 'bot' });
          }
        });
        setMessages(formattedMessages);
      } else {
        // Message de bienvenue si l'historique est vide
        setMessages([{ id: '1', text: "Bonjour ! Je suis l'assistant CYNA. Comment puis-je vous aider aujourd'hui ?", sender: 'bot' }]);
      }
    }
  }, [historyData]);

  // 2. Mutation pour envoyer un message
  const sendMessageMutation = useMutation({
    mutationFn: async (text: string) => {
      // On suppose que ton API attend { "message": "..." }
      return (await api.post('/chat', { message: text })).data;
    },
    onSuccess: (data) => {
      // On récupère la réponse de Laravel (on gère plusieurs formats possibles)
      const botText = data?.data?.response || data?.data?.bot_response || data?.response || data?.bot_response || "Désolé, je n'ai pas compris.";
      
      const botMsg: Message = {
        id: (Date.now() + 1).toString(),
        text: botText,
        sender: 'bot'
      };
      setMessages(prev => [...prev, botMsg]);
    },
    onError: () => {
      const errorMsg: Message = {
        id: (Date.now() + 1).toString(),
        text: "Une erreur est survenue. Vérifiez votre connexion et réessayez.",
        sender: 'bot'
      };
      setMessages(prev => [...prev, errorMsg]);
    }
  });

  const sendMessage = () => {
    if (!inputText.trim() || sendMessageMutation.isPending) return;

    const userMsg: Message = {
      id: Date.now().toString(),
      text: inputText,
      sender: 'user'
    };

    setMessages(prev => [...prev, userMsg]);
    setInputText('');

    // On lance la requête API
    sendMessageMutation.mutate(userMsg.text);
  };

  const renderMessage = ({ item }: { item: Message }) => (
    <View style={[styles.messageBubble, item.sender === 'user' ? styles.userBubble : styles.botBubble]}>
      <Text style={[styles.messageText, item.sender === 'user' && styles.userText]}>{item.text}</Text>
    </View>
  );

  return (
    <View style={styles.container}>
      {/* Header du chat */}
      <View style={styles.header}>
        <TouchableOpacity onPress={() => navigation.goBack()} style={styles.backBtn}>
          <Ionicons name="arrow-back" size={24} color="#fff" />
        </TouchableOpacity>
        <View style={styles.botAvatar}>
          <Ionicons name="hardware-chip" size={20} color="#fff" />
        </View>
        <View style={{ marginLeft: 10, flex: 1 }}>
          <Text style={styles.headerTitle}>Assistant CYNA</Text>
          <Text style={styles.headerStatus}>
            
            {sendMessageMutation.isPending ? "En train d'écrire..." : 'En ligne'}
          </Text>
        </View>
        <View style={[styles.onlineDot, !sendMessageMutation.isPending && styles.onlineDotActive]} />
      </View>

      {/* Zone de messages */}
      {loadingHistory ? (
        <View style={styles.loadingContainer}>
          <ActivityIndicator size="large" color="#0056b3" />
          <Text style={styles.loadingText}>Chargement de l'historique...</Text>
        </View>
      ) : (
        <FlatList
          ref={flatListRef}
          data={messages}
          keyExtractor={item => item.id}
          renderItem={renderMessage}
          contentContainerStyle={styles.messagesList}
          onContentSizeChange={() => flatListRef.current?.scrollToEnd({ animated: true })}
          onLayout={() => flatListRef.current?.scrollToEnd({ animated: false })}
        />
      )}

      {/* Zone de saisie */}
      <KeyboardAvoidingView behavior={Platform.OS === 'ios' ? 'padding' : undefined} style={styles.inputContainer}>
        <TextInput
          style={styles.input}
          value={inputText}
          onChangeText={setInputText}
          placeholder="Écrivez votre message..."
          placeholderTextColor="#94a3b8"
          multiline
          editable={!sendMessageMutation.isPending}
        />
        <TouchableOpacity 
          style={[styles.sendButton, (!inputText.trim() || sendMessageMutation.isPending) && styles.sendButtonDisabled]} 
          onPress={sendMessage}
          disabled={!inputText.trim() || sendMessageMutation.isPending}
        >
          {sendMessageMutation.isPending ? (
            <ActivityIndicator size="small" color="#fff" />
          ) : (
            <Ionicons name="send" size={20} color="#fff" />
          )}
        </TouchableOpacity>
      </KeyboardAvoidingView>
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#F8FAFC' },
  
  // Header
  header: { flexDirection: 'row', alignItems: 'center', backgroundColor: '#0056b3', paddingHorizontal: 15, paddingVertical: 15, paddingTop: 40 },
  backBtn: { padding: 5, marginRight: 10 },
  botAvatar: { width: 40, height: 40, borderRadius: 20, backgroundColor: 'rgba(255,255,255,0.2)', justifyContent: 'center', alignItems: 'center' },
  headerTitle: { color: '#fff', fontSize: 16, fontWeight: 'bold' },
  headerStatus: { color: 'rgba(255,255,255,0.8)', fontSize: 12 },
  onlineDot: { width: 10, height: 10, borderRadius: 5, backgroundColor: '#fbbf24' },
  onlineDotActive: { backgroundColor: '#2ecc71' },

  // Messages
  loadingContainer: { flex: 1, justifyContent: 'center', alignItems: 'center' },
  loadingText: { marginTop: 10, color: '#64748b' },
  messagesList: { padding: 20, flexGrow: 1, justifyContent: 'flex-end' },
  messageBubble: { maxWidth: '80%', padding: 15, borderRadius: 20, marginBottom: 12 },
  botBubble: { backgroundColor: '#fff', alignSelf: 'flex-start', borderBottomLeftRadius: 4, shadowColor: '#000', shadowOpacity: 0.05, shadowRadius: 4, elevation: 1, borderWidth: 1, borderColor: '#F1F5F9' },
  userBubble: { backgroundColor: '#0056b3', alignSelf: 'flex-end', borderBottomRightRadius: 4 },
  messageText: { fontSize: 15, color: '#1E293B', lineHeight: 22 },
  userText: { color: '#fff' },

  // Input
  inputContainer: { flexDirection: 'row', backgroundColor: '#fff', padding: 15, borderTopWidth: 1, borderColor: '#F1F5F9', alignItems: 'flex-end' },
  input: { flex: 1, backgroundColor: '#F1F5F9', borderRadius: 20, paddingHorizontal: 15, paddingVertical: 10, fontSize: 15, maxHeight: 100, color: '#1E293B' },
  sendButton: { backgroundColor: '#0056b3', width: 45, height: 45, borderRadius: 22, justifyContent: 'center', alignItems: 'center', marginLeft: 10 },
  sendButtonDisabled: { backgroundColor: '#cbd5e1' }
});